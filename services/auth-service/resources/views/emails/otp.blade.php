<x-email-layout title="Tu código de acceso">
  <h1 style="margin:0 0 12px;font-size:22px;color:#0b1512;">Tu código de verificación</h1>
  <p style="margin:0 0 20px;">Usa este código para {{ $context }}. Es válido durante {{ $minutes }} minutos.</p>
  <div style="text-align:center;margin:24px 0;">
    <span style="display:inline-block;padding:16px 28px;border-radius:14px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:34px;font-weight:800;letter-spacing:10px;">{{ $code }}</span>
  </div>
  <p style="margin:0;color:#8a978c;font-size:13px;">Nunca compartas este código con nadie. Si no intentaste iniciar sesión, cambia tu contraseña.</p>
</x-email-layout>
