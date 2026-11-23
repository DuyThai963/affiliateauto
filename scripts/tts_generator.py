#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
AffiliateAuto - Edge-TTS Vietnamese Speech Generator
Supports custom rate, voice, and JSON word-boundary metadata export.
"""

import sys
import os
import argparse
import asyncio
import json
import edge_tts

async def generate_speech(text: str, voice: str, rate: str, output_path: str, meta_path: str = None):
    output_dir = os.path.dirname(os.path.abspath(output_path))
    if output_dir and not os.path.exists(output_dir):
        os.makedirs(output_dir, exist_ok=True)

    max_retries = 3
    last_error = None

    for attempt in range(1, max_retries + 1):
        try:
            communicate = edge_tts.Communicate(text=text, voice=voice, rate=rate)
            submaker = edge_tts.SubMaker()
            audio_bytes = bytearray()

            async for chunk in communicate.stream():
                if chunk["type"] == "audio":
                    audio_bytes.extend(chunk["data"])
                elif chunk["type"] == "WordBoundary":
                    submaker.feed(chunk)

            if len(audio_bytes) > 0:
                with open(output_path, "wb") as audio_file:
                    audio_file.write(audio_bytes)

                if meta_path:
                    meta_dir = os.path.dirname(os.path.abspath(meta_path))
                    if meta_dir and not os.path.exists(meta_dir):
                        os.makedirs(meta_dir, exist_ok=True)
                    with open(meta_path, "w", encoding="utf-8") as meta_file:
                        meta_file.write(submaker.get_srt())

                print(f"SUCCESS:{os.path.abspath(output_path)}")
                return
            else:
                raise RuntimeError("Edge-TTS trả về audio rỗng (0 bytes).")
        except Exception as exc:
            last_error = exc
            if attempt < max_retries:
                await asyncio.sleep(1.5)

    if last_error:
        raise last_error

def main():
    parser = argparse.ArgumentParser(description="Edge-TTS Voice Generator")
    parser.add_argument("--text", type=str, default="", help="Text to synthesize")
    parser.add_argument("--text-file", type=str, default="", help="Path to text file (UTF-8)")
    parser.add_argument("--voice", type=str, default="vi-VN-HoaiMyNeural", help="Voice model")
    parser.add_argument("--rate", type=str, default="+5%", help="Speech rate adjustment")
    parser.add_argument("--output", type=str, required=True, help="Output MP3 file path")
    parser.add_argument("--sub-output", type=str, default="", help="Optional subtitle output path (SRT)")

    args = parser.parse_args()

    content = args.text
    if args.text_file and os.path.exists(args.text_file):
        with open(args.text_file, "r", encoding="utf-8") as f:
            content = f.read()

    if not content.strip():
        print("ERROR: Empty text provided.", file=sys.stderr)
        sys.exit(1)

    asyncio.run(generate_speech(
        text=content,
        voice=args.voice,
        rate=args.rate,
        output_path=args.output,
        meta_path=args.sub_output if args.sub_output else None
    ))

if __name__ == "__main__":
    main()

