#!/usr/bin/env python3
"""
DBS401 - Group 02
tools/decode_helper.py  –  Decode Helper for CTF Players

⚠️  Chỉ sử dụng trong môi trường lab DBS401 nội bộ.

Dùng script này để:
  1. Decode hex string → ASCII
  2. Decode base64 string → ASCII
  3. Reverse string
  4. Auto-detect và decode một chuỗi lạ
  5. Assemble flag từ các parts

Usage:
  python3 decode_helper.py --hex "4442533430317B53514C5F"
  python3 decode_helper.py --b64 "REJTNDAxezFET1JfVHI0bnNf"
  python3 decode_helper.py --rev "_n01tc3jn1"
  python3 decode_helper.py --auto "307234636C335F58337274217D"
  python3 decode_helper.py --assemble
"""

import argparse
import base64
import sys


BANNER = """
╔══════════════════════════════════════════════════════╗
║  DBS401 - Group 02  |  CTF Decode Helper             ║
║  Lab use only – Not for real systems                 ║
╚══════════════════════════════════════════════════════╝
"""


def hex_decode(hex_str: str) -> str:
    hex_str = hex_str.strip().replace(' ', '').replace('0x', '')
    try:
        return bytes.fromhex(hex_str).decode('utf-8')
    except Exception as e:
        return f"[ERROR] hex_decode: {e}"


def b64_decode(b64_str: str) -> str:
    b64_str = b64_str.strip()
    # Add padding if needed
    pad = 4 - len(b64_str) % 4
    if pad != 4:
        b64_str += '=' * pad
    try:
        return base64.b64decode(b64_str).decode('utf-8')
    except Exception as e:
        return f"[ERROR] b64_decode: {e}"


def reverse_str(s: str) -> str:
    return s.strip()[::-1]


def auto_detect(s: str) -> dict:
    """Try all decoders and return all successful results."""
    results = {}
    s = s.strip()

    # Try hex
    hex_clean = s.replace(' ', '').replace('0x', '')
    if all(c in '0123456789abcdefABCDEF' for c in hex_clean) and len(hex_clean) % 2 == 0:
        r = hex_decode(hex_clean)
        if '[ERROR]' not in r:
            results['hex'] = r

    # Try base64
    try:
        padded = s + '=' * (4 - len(s) % 4) if len(s) % 4 else s
        r = base64.b64decode(padded).decode('utf-8')
        results['base64'] = r
    except Exception:
        pass

    # Try reverse
    results['reversed'] = reverse_str(s)

    return results


def assemble_flag1():
    print("\n━━━ FLAG 1 ASSEMBLY ━━━")
    part_a_hex = "4442533430317B53514C5F"
    part_b_rev = "_n01tc3jn1"
    part_c_b64 = "MHI0Y2wzIX0="

    a = hex_decode(part_a_hex)
    b = reverse_str(part_b_rev)
    c = b64_decode(part_c_b64)

    print(f"  Part A (hex decode) : {part_a_hex!r}  →  {a!r}")
    print(f"  Part B (reverse)    : {part_b_rev!r}  →  {b!r}")
    print(f"  Part C (b64 decode) : {part_c_b64!r}  →  {c!r}")
    print(f"\n  ✅ FLAG 1 = {a + b + c}")
    return a + b + c


def assemble_flag2():
    print("\n━━━ FLAG 2 ASSEMBLY ━━━")
    part_a_b64 = "REJTNDAxezFET1JfVHI0bnNf"
    part_b_rev  = "}!w4lF_ss3cc4"

    a = b64_decode(part_a_b64)
    b = reverse_str(part_b_rev)

    print(f"  Part A (b64 decode) : {part_a_b64!r}  →  {a!r}")
    print(f"  Part B (reverse)    : {part_b_rev!r}  →  {b!r}")
    print(f"\n  ✅ FLAG 2 = {a + b}")
    return a + b


def assemble_flag3(part_a: str = ""):
    print("\n━━━ FLAG 3 ASSEMBLY ━━━")
    part_b_hex = "307234636C335F58337274217D"

    if not part_a:
        print("  Part A: (requires blind SQLi extraction from ADMIN_SECRETS)")
        print("          Run exploit_flag3_local.py to extract.")
        part_a = "DBS401{Bl1nd_B00l_"  # show expected value
        print(f"  Part A (expected)   : {part_a!r}")
    else:
        print(f"  Part A (extracted)  : {part_a!r}")

    b = hex_decode(part_b_hex)
    print(f"  Part B (hex decode) : {part_b_hex!r}  →  {b!r}")
    flag = part_a + b
    print(f"\n  ✅ FLAG 3 = {flag}")
    return flag


def main():
    print(BANNER)
    parser = argparse.ArgumentParser(description='DBS401 CTF Decode Helper')
    parser.add_argument('--hex',      help='Decode hex string to ASCII')
    parser.add_argument('--b64',      help='Decode base64 string to ASCII')
    parser.add_argument('--rev',      help='Reverse a string')
    parser.add_argument('--auto',     help='Auto-detect encoding and decode')
    parser.add_argument('--assemble', action='store_true',
                        help='Show all 3 flag assemblies')
    parser.add_argument('--flag3-part-a', default='',
                        help='Part A for Flag 3 (from blind SQLi extraction)')
    args = parser.parse_args()

    if args.hex:
        result = hex_decode(args.hex)
        print(f"Hex decode: {args.hex!r}")
        print(f"Result:     {result!r}")

    elif args.b64:
        result = b64_decode(args.b64)
        print(f"Base64 decode: {args.b64!r}")
        print(f"Result:        {result!r}")

    elif args.rev:
        result = reverse_str(args.rev)
        print(f"Reverse: {args.rev!r}")
        print(f"Result:  {result!r}")

    elif args.auto:
        print(f"Auto-detect decoding for: {args.auto!r}\n")
        results = auto_detect(args.auto)
        if not results:
            print("No successful decoding found.")
        for method, value in results.items():
            print(f"  [{method:8s}]  →  {value!r}")

    elif args.assemble:
        f1 = assemble_flag1()
        f2 = assemble_flag2()
        f3 = assemble_flag3(args.flag3_part_a)
        print("\n" + "="*55)
        print("  ALL FLAGS:")
        print(f"  Flag 1: {f1}")
        print(f"  Flag 2: {f2}")
        print(f"  Flag 3: {f3}")
        print("="*55)

    else:
        parser.print_help()
        print("\nExamples:")
        print("  python3 decode_helper.py --hex 4442533430317B53514C5F")
        print("  python3 decode_helper.py --b64 REJTNDAxezFET1JfVHI0bnNf")
        print("  python3 decode_helper.py --rev '_n01tc3jn1'")
        print("  python3 decode_helper.py --auto 307234636C335F58337274217D")
        print("  python3 decode_helper.py --assemble")


if __name__ == '__main__':
    main()
