# This script is deprecated. PaddleOCR is used in scripts/paddle_ocr_extract.py
import sys
import json

if __name__ == "__main__":
    from paddle_ocr_extract import extract_from_image
    if len(sys.argv) > 1:
        print(json.dumps(extract_from_image(sys.argv[1]), ensure_ascii=False))
    else:
        print(json.dumps({"success": False, "message": "Missing file argument"}))
