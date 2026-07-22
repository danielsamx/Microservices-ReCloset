<x-email-layout title="Bienvenido a ReCloset">
  <h1 style="margin:0 0 12px;font-size:22px;color:#0b1512;">¡Bienvenido, {{ $name }}! 🌱</h1>
  <p style="margin:0 0 16px;">Tu cuenta en <strong>ReCloset</strong> está lista. Ya puedes publicar prendas, explorar el catálogo y conversar en tiempo real con personas interesadas en tu ropa.</p>
  <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0;">
    <tr><td style="border-radius:12px;background:linear-gradient(135deg,#10b981,#047857);">
      <a href="{{ $url }}" style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;">Explorar el catálogo</a>
    </td></tr>
  </table>
  <p style="margin:0;color:#8a978c;font-size:13px;">Gracias por sumarte a la moda circular.</p>
</x-email-layout>
