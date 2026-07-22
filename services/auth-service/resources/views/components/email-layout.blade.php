@props(['title' => 'ReCloset'])
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4faf6;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0b1512;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4faf6;padding:32px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 30px -12px rgba(6,78,59,.22);">
          <tr>
            <td style="background:linear-gradient(120deg,#065f46 0%,#10b981 65%,#34d399 100%);padding:28px 32px;">
              <span style="display:inline-block;width:40px;height:40px;line-height:40px;text-align:center;border-radius:12px;background:rgba(255,255,255,.18);color:#ffffff;font-weight:800;font-size:20px;">R</span>
              <span style="color:#ffffff;font-size:20px;font-weight:800;letter-spacing:-.3px;vertical-align:middle;margin-left:10px;">ReCloset</span>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;line-height:1.6;font-size:15px;color:#33413a;">
              {{ $slot }}
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px;border-top:1px solid #eef4ef;color:#8a978c;font-size:12px;line-height:1.6;">
              Moda circular · Dale una segunda vida a tu ropa.<br>
              Si no reconoces esta actividad, puedes ignorar este correo con seguridad.
            </td>
          </tr>
        </table>
        <p style="color:#8a978c;font-size:11px;margin-top:16px;">© {{ date('Y') }} ReCloset</p>
      </td>
    </tr>
  </table>
</body>
</html>
