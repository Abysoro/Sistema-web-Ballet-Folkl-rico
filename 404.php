<?php
http_response_code(404);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Error 404 - Página no encontrada</title>

  <link rel="stylesheet" href="css/estilos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      min-height: 100vh;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background:
        radial-gradient(circle at top left, rgba(0,223,252,0.12), transparent 24%),
        radial-gradient(circle at bottom right, rgba(225,21,114,0.10), transparent 24%),
        linear-gradient(135deg, #020b14 0%, #0b2d45 55%, #153f5c 100%);
      font-family: var(--font-body, Arial, sans-serif);
      color: #ffffff;
    }

    .error-404-wrapper {
      width: min(720px, 92vw);
      padding: 28px;
    }

    .error-404-card {
      padding: 42px 36px;
      border-radius: 26px;
      text-align: center;
      background: linear-gradient(180deg, rgba(8,18,38,0.96), rgba(4,13,28,0.96));
      border: 1.5px solid rgba(0, 223, 252, 0.75);
      box-shadow: 0 24px 60px rgba(0,0,0,0.38);
    }

    .error-404-logo {
      width: 135px;
      height: auto;
      margin-bottom: 18px;
      filter: drop-shadow(0 0 16px rgba(0,223,252,0.28));
    }

    .error-404-numero {
      margin: 0;
      color: #00dffc;
      font-family: var(--font-display, Arial, sans-serif);
      font-size: 5rem;
      font-weight: 900;
      letter-spacing: 0.08em;
      line-height: 1;
      text-shadow: 0 0 18px rgba(0,223,252,0.28);
    }

    .error-404-card h1 {
      margin: 16px 0 12px;
      color: #ffffff;
      font-family: var(--font-display, Arial, sans-serif);
      font-size: 2rem;
      font-weight: 900;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .error-404-card p {
      max-width: 560px;
      margin: 0 auto 28px;
      color: #d7ecf7;
      font-size: 1rem;
      line-height: 1.65;
    }

    .error-404-actions {
      display: flex;
      justify-content: center;
      gap: 14px;
      flex-wrap: wrap;
    }

    .error-404-btn {
      min-width: 180px;
      padding: 13px 20px;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      text-decoration: none;
      font-family: var(--font-display, Arial, sans-serif);
      font-weight: 900;
      letter-spacing: 0.07em;
      text-transform: uppercase;
      transition: 0.25s ease;
    }

    .error-404-btn.principal {
      background: linear-gradient(90deg, #00b4cc, #00dffc);
      color: #042c39;
      border: 1px solid #00e5ff;
    }

    .error-404-btn.secundario {
      background: rgba(225, 21, 114, 0.10);
      color: #ffd7e7;
      border: 1px solid rgba(225, 21, 114, 0.65);
    }

    .error-404-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 0 18px rgba(0,223,252,0.22);
    }

    @media (max-width: 520px) {
      .error-404-card {
        padding: 34px 22px;
      }

      .error-404-numero {
        font-size: 4rem;
      }

      .error-404-card h1 {
        font-size: 1.45rem;
      }

      .error-404-btn {
        width: 100%;
      }
    }
  </style>
</head>

<body>
  <main class="error-404-wrapper">
    <section class="error-404-card">
      <img src="/sistema_ballet/img/s1.png" alt="Logo Sueño y Danza" class="error-404-logo">

      <h2 class="error-404-numero">404</h2>

      <h1>Página no encontrada</h1>

      <p>
        La dirección solicitada no existe o fue escrita incorrectamente.
        Verifica el enlace o vuelve al panel principal del sistema.
      </p>

      <div class="error-404-actions">
        <a href="/sistema_ballet/index.php" class="error-404-btn principal">
          <i class="fa-solid fa-house"></i>
          Ir al inicio
        </a>

        <a href="/sistema_ballet/php/login.php" class="error-404-btn secundario">
          <i class="fa-solid fa-right-to-bracket"></i>
          Ir al login
        </a>
      </div>
    </section>
  </main>
</body>
</html>