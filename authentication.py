#!/usr/bin/env python3
import argparse
import json
import os
import smtplib
import ssl
import sys
from pathlib import Path
from email.message import EmailMessage


def load_local_config() -> dict:
    base_path = Path(__file__).resolve().parent
    config_paths = [
        base_path / "db" / "json_data" / "email_config.json",
        base_path / "email_config.json",
    ]

    for config_path in config_paths:
        if not config_path.exists() or config_path.stat().st_size == 0:
            continue

        try:
            with config_path.open("r", encoding="utf-8") as handle:
                data = json.load(handle)
                return data if isinstance(data, dict) else {}
        except Exception:
            continue

    return {}


def config_location_text() -> str:
    return str(Path(__file__).resolve().parent / "db" / "json_data" / "email_config.json")


def get_setting(config: dict, key: str, default: str = "") -> str:
    env_key = f"UBFIND_{key}"
    value = os.environ.get(env_key)
    if value is not None and value != "":
        return value
    value = config.get(key.lower())
    return str(value) if value is not None else default


def build_html(code: str, recipient_name: str) -> str:
    safe_name = recipient_name.strip() or "there"
    return f"""<!doctype html>
<html>
<body style="margin:0;background:#f5f6f8;font-family:Arial,Helvetica,sans-serif;color:#171b24;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f6f8;padding:28px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 34px rgba(15,23,42,.12);">
          <tr>
            <td style="background:linear-gradient(135deg,#8f0018,#a9001d 58%,#760013);padding:26px 32px;color:#ffffff;">
              <table role="presentation" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="width:66px;">
                    <div style="width:54px;height:54px;border-radius:50%;background:#f2ad00;border:2px solid #ffd665;text-align:center;line-height:54px;color:#8f0018;font-weight:900;font-size:18px;">UB</div>
                  </td>
                  <td>
                    <div style="font-size:34px;font-weight:900;letter-spacing:.02em;color:#ffd329;line-height:1;">UB FIND</div>
                    <div style="font-size:15px;font-weight:900;text-transform:uppercase;letter-spacing:.03em;margin-top:5px;">Lost and Found System</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:34px 40px 26px;">
              <h1 style="margin:0 0 20px;font-size:18px;line-height:1.3;color:#171b24;">Hello, {safe_name}</h1>
              <p style="margin:0 0 14px;font-size:14px;line-height:1.7;">Welcome to UB FIND - Lost and Found System.</p>
              <p style="margin:0 0 24px;font-size:14px;line-height:1.7;">Use the verification code below to continue logging in to your account:</p>
              <div style="border:1px solid #dfe4ed;border-radius:10px;padding:28px 20px;text-align:center;background:#ffffff;">
                <div style="font-size:13px;font-weight:900;letter-spacing:.08em;color:#303948;text-transform:uppercase;">Authentication Code</div>
                <div style="margin-top:12px;font-size:42px;font-weight:900;letter-spacing:.28em;color:#8f1624;line-height:1;">{code}</div>
              </div>
              <p style="margin:22px 0 0;text-align:center;font-size:14px;font-weight:800;">This code will expire in <span style="color:#8f1624;">5 minutes</span>.</p>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:28px;background:#fff6df;border-radius:10px;">
                <tr>
                  <td style="width:56px;padding:18px 0 18px 22px;font-size:26px;color:#b57900;">&#9888;</td>
                  <td style="padding:18px 22px 18px 0;font-size:13px;line-height:1.55;color:#171b24;">If you did not request this code,<br>please ignore this email.</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:26px 40px;background:#fafafa;border-top:1px solid #edf0f5;">
              <table role="presentation" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="width:58px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:#eef0f3;text-align:center;line-height:42px;color:#606875;font-size:22px;">&#9742;</div>
                  </td>
                  <td style="font-size:13px;line-height:1.45;color:#171b24;">
                    Thank you,<br>
                    <strong style="color:#8f1624;">UB Find Team</strong><br>
                    University of Batangas<br>
                    Lost and Found System
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>"""


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--to", required=True)
    parser.add_argument("--code", required=True)
    parser.add_argument("--name", default="")
    args = parser.parse_args()

    config = load_local_config()
    host = get_setting(config, "SMTP_HOST", "smtp.gmail.com")
    user = get_setting(config, "SMTP_USER")
    password = get_setting(config, "SMTP_PASS")
    port = int(get_setting(config, "SMTP_PORT", "587"))
    from_email = get_setting(config, "FROM_EMAIL", user or "ubfind@ub.edu.ph")
    from_name = get_setting(config, "FROM_NAME", "UB Find")

    if not user or not password:
        print(json.dumps({
            "success": False,
            "error": f"SMTP credentials are missing. Fill in {config_location_text()} with a Gmail address and app password."
        }))
        return 0

    message = EmailMessage()
    message["Subject"] = "Your UB Find authentication code"
    message["From"] = f"{from_name} <{from_email}>"
    message["To"] = args.to
    message.set_content(
        f"Hello,\n\nYour UB Find authentication code is {args.code}.\n\n"
        "This code expires in 5 minutes.\n\nIf you did not request this code, please ignore this email.\n\nUB Find Team"
    )
    message.add_alternative(build_html(args.code, args.name), subtype="html")

    try:
        if port == 465:
            context = ssl.create_default_context()
            with smtplib.SMTP_SSL(host, port, context=context, timeout=20) as smtp:
                smtp.login(user, password)
                smtp.send_message(message)
        else:
            with smtplib.SMTP(host, port, timeout=20) as smtp:
                smtp.starttls(context=ssl.create_default_context())
                smtp.login(user, password)
                smtp.send_message(message)
        print(json.dumps({"success": True}))
    except Exception as exc:
        print(json.dumps({"success": False, "error": str(exc)}))

    return 0


if __name__ == "__main__":
    sys.exit(main())
