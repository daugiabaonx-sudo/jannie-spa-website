#!/usr/bin/env python3
"""
Restore faces in a Real-ESRGAN 4x master PNG using YuNet (5-point landmark detection)
and CodeFormer ONNX (512x512 FFHQ alignment + LAB colour-matched feathered blending).

Invoked from `scripts/upscale-step-3-media.mjs` via `uv run` when an item declares
`"face_restore": true`.

Usage:
  uv run --with onnxruntime --with numpy --with opencv-python-headless \
    python3 scripts/lib/jn-restore-faces.py <image.png> <model_dir> [fidelity]
"""

import os
import sys
import cv2
import numpy as np
import onnxruntime as ort

FFHQ_TEMPLATE = np.array(
    [
        [192.98138, 239.94708],
        [318.90277, 240.19360],
        [256.63416, 314.01935],
        [201.26117, 371.41043],
        [313.08905, 371.15118],
    ],
    dtype=np.float32,
)

MIN_FACE_SCORE = 0.90


def match_lab_stats(src_bgr: np.ndarray, dst_bgr: np.ndarray) -> np.ndarray:
    """Match LAB channel mean and standard deviation so skin tone never shifts."""
    src_lab = cv2.cvtColor(src_bgr, cv2.COLOR_BGR2LAB).astype(np.float32)
    dst_lab = cv2.cvtColor(dst_bgr, cv2.COLOR_BGR2LAB).astype(np.float32)
    for ch in range(3):
        s_mean, s_std = src_lab[..., ch].mean(), src_lab[..., ch].std() + 1e-5
        d_mean, d_std = dst_lab[..., ch].mean(), dst_lab[..., ch].std() + 1e-5
        dst_lab[..., ch] = np.clip((dst_lab[..., ch] - d_mean) * (s_std / d_std) + s_mean, 0, 255)
    return cv2.cvtColor(dst_lab.astype(np.uint8), cv2.COLOR_LAB2BGR).astype(np.float32)


def restore_image(image_path: str, model_dir: str, fidelity: float = 0.6) -> int:
    yunet_path = os.path.join(model_dir, "yunet.onnx")
    codeformer_path = os.path.join(model_dir, "codeformer.onnx")
    if not os.path.isfile(yunet_path) or not os.path.isfile(codeformer_path):
        raise RuntimeError(f"Missing yunet.onnx or codeformer.onnx in {model_dir}")

    img_bgr = cv2.imread(image_path, cv2.IMREAD_COLOR)
    if img_bgr is None:
        raise RuntimeError(f"Could not decode image: {image_path}")

    h, w = img_bgr.shape[:2]
    scale = min(1.0, 1600.0 / max(h, w))
    det_img = (
        cv2.resize(img_bgr, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)
        if scale < 1.0
        else img_bgr
    )
    dh, dw = det_img.shape[:2]
    detector = cv2.FaceDetectorYN.create(yunet_path, "", (dw, dh), 0.5, 0.3, 5000)
    _, faces = detector.detect(det_img)
    if faces is None or len(faces) == 0:
        return 0

    sess = ort.InferenceSession(codeformer_path, providers=["CPUExecutionProvider"])
    out = img_bgr.copy().astype(np.float32)

    mask_512 = np.zeros((512, 512), dtype=np.float32)
    cv2.ellipse(mask_512, (256, 265), (175, 215), 0, 0, 360, 1.0, -1)
    mask_512 = cv2.GaussianBlur(mask_512, (61, 61), 0)

    restored_count = 0
    for face in faces:
        if float(face[-1]) < MIN_FACE_SCORE:
            continue
        pts = face[4:14].reshape(5, 2).astype(np.float32) / scale
        m_mat, _ = cv2.estimateAffinePartial2D(pts, FFHQ_TEMPLATE, method=cv2.LMEDS)
        if m_mat is None:
            continue
        cropped = cv2.warpAffine(
            img_bgr, m_mat, (512, 512), flags=cv2.INTER_CUBIC, borderMode=cv2.BORDER_REFLECT_101
        )
        rgb = cv2.cvtColor(cropped, cv2.COLOR_BGR2RGB).astype(np.float32) / 255.0
        inp = ((rgb - 0.5) / 0.5).transpose(2, 0, 1)[None, ...].astype(np.float32)
        pred = sess.run(None, {"x": inp, "w": np.array(fidelity, dtype=np.float64)})[0][0]
        pred = np.clip((pred.transpose(1, 2, 0) * 0.5 + 0.5) * 255.0, 0, 255).astype(np.uint8)
        restored_bgr = match_lab_stats(cropped, cv2.cvtColor(pred, cv2.COLOR_RGB2BGR))

        inv_m = cv2.invertAffineTransform(m_mat)
        warped_face = cv2.warpAffine(restored_bgr, inv_m, (w, h), flags=cv2.INTER_CUBIC)
        warped_mask = cv2.warpAffine(mask_512, inv_m, (w, h), flags=cv2.INTER_LINEAR)[..., None]
        out = out * (1.0 - warped_mask) + warped_face * warped_mask
        restored_count += 1

    # Subtle local contrast / unsharp mask on the 4x master so fine textures survive downscaling.
    final_u8 = np.clip(out, 0, 255).astype(np.uint8)
    blurred = cv2.GaussianBlur(final_u8, (0, 0), 1.4)
    sharpened = cv2.addWeighted(final_u8, 1.35, blurred, -0.35, 0)

    tmp_path = image_path + ".tmp.png"
    if not cv2.imwrite(tmp_path, sharpened):
        raise RuntimeError(f"Could not write temporary PNG: {tmp_path}")
    os.replace(tmp_path, image_path)
    return restored_count


if __name__ == "__main__":
    if len(sys.argv) < 3:
        sys.exit("Usage: jn-restore-faces.py <image.png> <model_dir> [fidelity]")
    fidelity_arg = float(sys.argv[3]) if len(sys.argv) > 3 else 0.6
    count = restore_image(sys.argv[1], sys.argv[2], fidelity_arg)
    print(f"faces={count}")

