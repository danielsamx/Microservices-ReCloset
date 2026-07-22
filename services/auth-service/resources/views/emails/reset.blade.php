<x-email-layout title="Restablece tu contraseña">
  <h1 style="margin:0 0 12px;font-size:22px;color:#0b1512;">Restablece tu contraseña</h1>
  <p style="margin:0 0 16px;">Hola {{ $name }}, recibimos una solicitud para restablecer la contraseña de tu cuenta. Pulsa el botón para crear una nueva.</p>
  <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0;">
    <tr><td style="border-radius:12px;background:linear-gradient(135deg,#10b981,#047857);">
      <a href="{{ $url }}" style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;">Crear nueva contraseña</a>
    </td></tr>
  </table>
  <p style="margin:0 0 8px;color:#8a978c;font-size:13px;">Si el botón no funciona, copia y pega este enlace:</p>
  <p style="margin:0;word-break:break-all;font-size:12px;color:#047857;">{{ $url }}</p>
  <p style="margin:16px 0 0;color:#8a978c;font-size:13px;">El enlace expira en 60 minutos. Si no solicitaste esto, ignora este correo: tu contraseña seguirá igual.</p>
</x-email-layout>
