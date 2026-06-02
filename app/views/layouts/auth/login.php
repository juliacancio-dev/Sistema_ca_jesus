<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestão de Estoque - C.A de Jesus</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .welcome-section {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        }

        .text-accent {
            color: #ef4444;
        }

        .border-accent {
            border-color: #ef4444;
        }

        .btn-primary {
            background-color: #2563eb;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-blue-500 to-blue-400 p-6">

        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl flex flex-col md:flex-row overflow-hidden">
            
            <div class="w-full md:w-1/2 welcome-section flex items-center justify-center p-10 text-white">
                <div class="text-center">
                    <div class="mb-8">
                        <img src="../public/assets/img/logoLogin.png" class="mx-auto w-48 md:w-72 h-auto" alt="Logo">
                        <h2 class="welcome-title text-3xl md:text-4xl font-bold mb-4">Bem-vindo ao</h2>
                        <h1 class="welcome-title text-4xl md:text-5xl font-bold mb-6 text-orange-300">C.A DE JESUS</h1>
                    </div>

                    <div class="space-y-4 text-left text-sm md:text-base">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-orange-300 mt-1 mr-3"></i>
                            <p class="text-blue-100">Sistema de gestão de estoque</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-orange-300 mt-1 mr-3"></i>
                            <p class="text-blue-100">Controle em tempo real</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-orange-300 mt-1 mr-3"></i>
                            <p class="text-blue-100">Relatórios automatizados</p>
                        </div>
                    </div>

                    <div class="mt-10 pt-6 border-t border-blue-400">
                        <p class="text-blue-200 italic text-sm md:text-base">"Soluções inteligentes para sua gestão"</p>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/2 p-8 flex items-center justify-center">
                <div class="w-full max-w-md">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">Acesse sua conta</h1>
                        <p class="text-gray-600">Entre com suas credenciais para continuar</p>
                    </div>

                    <?php
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?= $_SESSION['error'] ?>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form action="/sistema_ca_jesus/public/login" method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-blue-600"></i>
                                </div>
                                <input type="email" name="email" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="seu@email.com" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-blue-600"></i>
                                </div>
                                <input type="password" name="senha" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Sua senha" required>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200 flex items-center justify-center space-x-2">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Entrar</span>
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/public/assets/js/app.js"></script>
</body>

</html>