<?php
require_once 'includes/auth.php';

// Define a variável global $usuario para uso nas páginas
$usuario = getUsuario();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveNow - Aluguel de Carros</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .faq-box {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
        }

        .faq-box.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }

        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            position: absolute;
            left: 0;
            width: 40px;
            height: 100%;
            cursor: pointer;
            opacity: 0; /* invisível mas ainda clicável */
        }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="fixed w-full bg-white backdrop-blur-md z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20 items-center">
            <div class="flex items-center">
                  <span class="text-xl font-bold text-indigo-600 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -15.43 122.88 122.88" fill="currentColor" class="h-6 w-6 mr-2 text-indigo-600">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.17,34.23c-10.98-5.58-9.72-11.8,1.31-11.15l2.47,4.63l5.09-15.83C21.04,5.65,24.37,0,30.9,0H96c6.53,0,10.29,5.54,11.87,11.87l3.82,15.35l2.2-4.14c11.34-0.66,12.35,5.93,0.35,11.62l1.95,2.99c7.89,8.11,7.15,22.45,5.92,42.48v8.14c0,2.04-1.67,3.71-3.71,3.71h-15.83c-2.04,0-3.71-1.67-3.71-3.71v-4.54H24.04v4.54c0,2.04-1.67,3.71-3.71,3.71H4.5c-2.04,0-3.71-1.67-3.71-3.71V78.2c0-0.2,0.02-0.39,0.04-0.58C-0.37,62.25-2.06,42.15,10.17,34.23zM30.38,58.7l-14.06-1.77c-3.32-0.37-4.21,1.03-3.08,3.89l1.52,3.69c0.49,0.95,1.14,1.64,1.9,2.12c0.89,0.55,1.96,0.82,3.15,0.87l12.54,0.1c3.03-0.01,4.34-1.22,3.39-4C34.96,60.99,33.18,59.35,30.38,58.7zM54.38,52.79h14.4c0.85,0,1.55,0.7,1.55,1.55s-0.7,1.55-1.55,1.55h-14.4c-0.85,0-1.55-0.7-1.55-1.55S53.52,52.79,54.38,52.79zM89.96,73.15h14.4c0.85,0,1.55,0.7,1.55,1.55s-0.7,1.55-1.55,1.55h-14.4c-0.85,0-1.55-0.7-1.55-1.55S89.1,73.15,89.96,73.15zM92.5,58.7l14.06-1.77c3.32-0.37,4.21,1.03,3.08,3.89l-1.52,3.69c-0.49,0.95-1.14,1.64-1.9,2.12c-0.89,0.55-1.96,0.82-3.15,0.87l-12.54,0.1c-3.03-0.01-4.34-1.22-3.39-4C87.92,60.99,89.7,59.35,92.5,58.7zM18.41,73.15h14.4c0.85,0,1.55,0.7,1.55,1.55s-0.7,1.55-1.55,1.55h-14.4c-0.85,0-1.55-0.7-1.55-1.55S17.56,73.15,18.41,73.15zM19.23,31.2h86.82l-3.83-15.92c-1.05-4.85-4.07-9.05-9.05-9.05H33.06c-4.97,0-7.52,4.31-9.05,9.05L19.23,31.2z"/>
                    </svg>
                    <a href="#hero">DriveNow</a>
                  </span>
            </div>
            <div class="hidden md:flex space-x-8 items-center">
                <a href="#como-funciona" class="text-gray-700 hover:text-indigo-600 transition-colors duration-200">Como
                    funciona</a>
                <a href="#carros" class="text-gray-700 hover:text-indigo-600 transition-colors duration-200">Carros</a>
                <a href="#depoimentos" class="text-gray-700 hover:text-indigo-600 transition-colors duration-200">Depoimentos</a>
                <?php if (!estaLogado()): ?>
                    <button class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors" onclick="window.location.href='login.php'">
                        Login
                    </button>
                <?php elseif (estaLogado()): ?>
                    <button id="dropdownInformationButton" data-dropdown-toggle="dropdownInformation" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
                        <?= htmlspecialchars($usuario['primeiro_nome']) ?>
                        <svg class="w-2.5 h-2.5 ms-3 ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <!-- Dropdown menu -->
                    <div id="dropdownInformation" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 dark:divide-gray-600">
                        <div class="px-4 py-3 text-sm text-gray-900 dark:text-white">
<!--                            <div>--><?php //= htmlspecialchars($usuario['e_mail']) ?><!--</div>-->
                            <div class="font-medium truncate"><?= htmlspecialchars($usuario['e_mail']) ?></div>
                        </div>
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownInformationButton">
                            <li>
                                <a href="vboard.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Dashboard</a>
                            </li>
                            <li>
                                <a href="perfil/editar.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Settings</a>
                            </li>
                        </ul>
                        <div class="py-2">
                            <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Sign out</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="md:hidden">
                <button class="text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section id="hero" class="pt-32 pb-16 lg:pt-40 lg:pb-24 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-12 items-center">
        <div class="space-y-6 fade-in">
                <span class="inline-block px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                    🔥 Novo no Brasil
                </span>


            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
                Alugue carros de pessoas <span class="text-indigo-600">reais</span> ou alugue o seu
            </h1>

            <p class="text-xl text-gray-600 max-w-lg">
                A plataforma que conecta proprietários de veículos a pessoas que precisam alugar de forma prática,
                segura e sem burocracia.
            </p>

            <div class="bg-white shadow-xl rounded-2xl p-6">
                <div class="grid gap-4">
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Localização</label>
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <input type="text" placeholder="Buscar por cidade"
                                       class="w-full pl-10 pr-4 py-2 border rounded-lg">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                            <div class="relative">
                                <!-- Ícone SVG -->
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-5 w-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>

                                <!-- Campo de data -->
                                <input
                                        type="date"
                                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none">
                                <option value="">Selecione o tipo</option>
                                <option value="sedan">Sedan</option>
                                <option value="suv">SUV</option>
                                <option value="hatch">Hatchback</option>
                                <option value="pickup">Pickup</option>
                            </select>
                        </div>
                    </div>
                    <button class="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Buscar carros
                    </button>
                </div>
            </div>
        </div>

        <div class="relative hidden md:block fade-in">
            <div class="absolute -inset-2 bg-indigo-100 rounded-3xl transform rotate-3"></div>
            <img
                    src="https://images.unsplash.com/photo-1553440569-bcc63803a83d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                    alt="Aluguel de carros"
                    class="w-full h-[500px] object-cover rounded-2xl shadow-lg relative z-10"
            >
            <div class="absolute -bottom-4 -left-4 bg-white p-4 rounded-xl shadow-lg z-20">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Verificado e seguro</p>
                        <p class="text-sm text-gray-500">Todos os carros são vistoriados</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Como Funciona -->
<section id="como-funciona" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Como funciona</h2>
            <p class="text-xl text-gray-600">Conheça o processo simples para alugar ou cadastrar seu veículo</p>
        </div>

        <div class="grid md:grid-cols-2 gap-16 items-center">
            <div class="space-y-8">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold">Busque o carro ideal</h4>
                        <p class="text-gray-600">Encontre o veículo perfeito para sua necessidade</p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold">Escolha as datas</h4>
                        <p class="text-gray-600">Selecione o período que você precisará do veículo</p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold">Faça a reserva</h4>
                        <p class="text-gray-600">Confirme a reserva e efetue o pagamento com segurança</p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold">Retire o veículo</h4>
                        <p class="text-gray-600">Combine com o proprietário o local de retirada e devolução</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute inset-0 bg-indigo-100 rounded-3xl transform -rotate-2"></div>
                <img
                        src="https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                        alt="Processo"
                        class="relative z-10 w-full h-[400px] object-cover rounded-2xl shadow-lg"
                >
            </div>
        </div>
    </div>
</section>

<!-- Veículos em Destaque -->
<section id="carros" class="py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Veículos em destaque</h2>
            <p class="text-xl text-gray-600">Conheça alguns dos carros disponíveis para aluguel na nossa plataforma</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Card 1 -->
            <div class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="relative">
                    <img
                            src="https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="Jeep Renegade"
                            class="w-full h-48 object-cover"
                    >
                    <span class="absolute top-4 right-4 bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                            Disponível
                        </span>
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold text-gray-900">Jeep Renegade</h3>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="ml-1 text-sm font-medium">4.9</span>
                        </div>
                    </div>
                    <div class="flex items-center text-gray-600 mb-4">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span class="text-sm">São Paulo, SP</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-2xl font-bold text-indigo-600">R$ 189</span>
                            <span class="text-gray-600">/dia</span>
                        </div>
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                            Reservar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="relative">
                    <img
                            src="https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="Honda Civic"
                            class="w-full h-48 object-cover"
                    >
                    <span class="absolute top-4 right-4 bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                            Disponível
                        </span>
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold text-gray-900">Honda Civic</h3>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="ml-1 text-sm font-medium">4.8</span>
                        </div>
                    </div>
                    <div class="flex items-center text-gray-600 mb-4">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span class="text-sm">Rio de Janeiro, RJ</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-2xl font-bold text-indigo-600">R$ 165</span>
                            <span class="text-gray-600">/dia</span>
                        </div>
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                            Reservar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="relative">
                    <img
                            src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="Toyota Corolla"
                            class="w-full h-48 object-cover"
                    >
                    <span class="absolute top-4 right-4 bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                            Indisponível
                        </span>
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold text-gray-900">Toyota Corolla</h3>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="ml-1 text-sm font-medium">4.7</span>
                        </div>
                    </div>
                    <div class="flex items-center text-gray-600 mb-4">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span class="text-sm">Belo Horizonte, MG</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-2xl font-bold text-indigo-600">R$ 175</span>
                            <span class="text-gray-600">/dia</span>
                        </div>
                        <button class="bg-gray-300 text-gray-500 px-4 py-2 rounded-lg cursor-not-allowed">
                            Indisponível
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-12 text-center">
            <button class="inline-flex items-center px-6 py-3 border-2 border-indigo-600 text-indigo-600 font-medium rounded-lg hover:bg-indigo-50 transition-colors">
                Ver mais veículos
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </button>
        </div>
    </div>
</section>

<!-- Depoimentos -->
<section id="depoimentos" class="py-16 md:py-24 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">O que nossos usuários dizem</h2>
            <p class="text-xl text-gray-600 max-w-xl mx-auto">Experiências reais de pessoas que usam nossa
                plataforma</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Depoimento 1 -->
            <div class="bg-white p-6 rounded-xl shadow-md animate-on-scroll">
                <div class="flex items-center mb-4">
                    <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80"
                         alt="Roberto Silva" class="w-14 h-14 rounded-full object-cover mr-4"/>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Roberto Silva</h3>
                        <p class="text-indigo-600">Proprietário</p>
                    </div>
                </div>
                <p class="text-gray-600 italic">"Consigo uma renda extra quando não estou usando meu carro. Já paguei
                    duas parcelas só com os aluguéis!"</p>
                <div class="mt-4 flex gap-1">
                    <span class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span>
                </div>
            </div>

            <!-- Depoimento 2 -->
            <div class="bg-white p-6 rounded-xl shadow-md animate-on-scroll">
                <div class="flex items-center mb-4">
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80"
                         alt="Juliana Mendes" class="w-14 h-14 rounded-full object-cover mr-4"/>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Juliana Mendes</h3>
                        <p class="text-indigo-600">Locatária</p>
                    </div>
                </div>
                <p class="text-gray-600 italic">"Economizo muito alugando carros na plataforma. Além disso, encontro
                    modelos que as locadoras tradicionais não oferecem."</p>
                <div class="mt-4 flex gap-1">
                    <span class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span>
                </div>
            </div>

            <!-- Depoimento 3 -->
            <div class="bg-white p-6 rounded-xl shadow-md animate-on-scroll">
                <div class="flex items-center mb-4">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80"
                         alt="Carlos Oliveira" class="w-14 h-14 rounded-full object-cover mr-4"/>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Carlos Oliveira</h3>
                        <p class="text-indigo-600">Proprietário</p>
                    </div>
                </div>
                <p class="text-gray-600 italic">"O processo é muito mais simples do que imaginei. A plataforma cuida de
                    tudo e eu só preciso entregar e receber o carro."</p>
                <div class="mt-4 flex gap-1">
                    <span class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span>
                </div>
            </div>

            <!-- Depoimento 4 -->
            <div class="bg-white p-6 rounded-xl shadow-md animate-on-scroll">
                <div class="flex items-center mb-4">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80"
                         alt="Fernanda Lima" class="w-14 h-14 rounded-full object-cover mr-4"/>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Fernanda Lima</h3>
                        <p class="text-indigo-600">Locatária</p>
                    </div>
                </div>
                <p class="text-gray-600 italic">"Já tive problemas com locadoras tradicionais. Aqui, a experiência é
                    muito mais pessoal e acolhedora."</p>
                <div class="mt-4 flex gap-1">
                    <span class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span>
                </div>
            </div>

            <!-- Depoimento 5 -->
            <div class="bg-white p-6 rounded-xl shadow-md animate-on-scroll">
                <div class="flex items-center mb-4">
                    <img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80"
                         alt="Ricardo Almeida" class="w-14 h-14 rounded-full object-cover mr-4"/>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Ricardo Almeida</h3>
                        <p class="text-indigo-600">Proprietário</p>
                    </div>
                </div>
                <p class="text-gray-600 italic">"Meu carro ficava parado a semana toda. Agora, além de gerar renda, ele
                    está sempre em movimento!"</p>
                <div class="mt-4 flex gap-1">
                    <span class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span>
                </div>
            </div>

            <!-- Depoimento 6 -->
            <div class="bg-white p-6 rounded-xl shadow-md animate-on-scroll">
                <div class="flex items-center mb-4">
                    <img src="https://images.unsplash.com/photo-1508214751196-bc2ad224ebd5?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80"
                         alt="Mariana Costa" class="w-14 h-14 rounded-full object-cover mr-4"/>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Mariana Costa</h3>
                        <p class="text-indigo-600">Locatária</p>
                    </div>
                </div>
                <p class="text-gray-600 italic">"As fotos e descrições são precisas e os proprietários são super
                    atenciosos. Recomendo muito!"</p>
                <div class="mt-4 flex gap-1">
                    <span class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span><span class="text-yellow-500">&#9733;</span><span
                        class="text-yellow-500">&#9733;</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-24 bg-indigo-600 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">Pronto para começar?</h2>
        <p class="text-xl text-indigo-100 mb-8">
            Junte-se a milhares de pessoas que já estão economizando ou gerando renda extra
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button class="bg-white text-indigo-600 px-8 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                Cadastrar meu veículo
            </button>
            <button class="border-2 border-white text-white px-8 py-3 rounded-lg hover:bg-indigo-700 transition-colors">
                Alugar um carro
            </button>
        </div>
    </div>
</section>

<section class="py-16 md:py-24 px-4 sm:px-6">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Perguntas frequentes</h2>
            <p class="text-xl text-gray-600">Tire suas dúvidas sobre nossa plataforma</p>
        </div>

        <div class="space-y-6">
            <div class="faq-box animate-on-scroll">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Como funciona o seguro dos veículos?</h3>
                <p class="text-gray-600">Todos os aluguéis incluem cobertura contra colisão e roubo. O locatário pode
                    optar por proteções adicionais durante o processo de reserva.</p>
            </div>

            <div class="faq-box animate-on-scroll">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Posso cancelar uma reserva?</h3>
                <p class="text-gray-600">Sim, as reservas podem ser canceladas até 24 horas antes do início do aluguel
                    sem custos adicionais. Cancelamentos posteriores podem estar sujeitos a taxas.</p>
            </div>

            <div class="faq-box animate-on-scroll">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Como verifico o estado do veículo na entrega e
                    devolução?</h3>
                <p class="text-gray-600">Nosso aplicativo possui uma ferramenta de vistoria digital, onde proprietário e
                    locatário registram fotos e observações sobre o estado do veículo.</p>
            </div>

            <div class="faq-box animate-on-scroll">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Quanto posso ganhar alugando meu carro?</h3>
                <p class="text-gray-600">Os ganhos variam conforme o modelo, ano e condição do veículo. Em média,
                    proprietários ganham entre R$ 1.200 e R$ 3.500 por mês, dependendo da disponibilidade.</p>
            </div>

            <div class="faq-box animate-on-scroll">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Quem pode alugar meu carro?</h3>
                <p class="text-gray-600">Apenas usuários verificados com CNH válida, histórico de condução limpo e
                    avaliações positivas na plataforma podem solicitar o aluguel do seu veículo.</p>
            </div>

            <div class="faq-box animate-on-scroll">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Como são feitos os pagamentos?</h3>
                <p class="text-gray-600">Os pagamentos são processados através da plataforma. O valor é reservado quando
                    a reserva é confirmada e liberado para o proprietário 24 horas após a retirada do veículo.</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid md:grid-cols-4 gap-8">
            <div>
                <div class="flex items-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -15.43 122.88 122.88" fill="currentColor" class="h-6 w-6 mr-2 text-indigo-600">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.17,34.23c-10.98-5.58-9.72-11.8,1.31-11.15l2.47,4.63l5.09-15.83C21.04,5.65,24.37,0,30.9,0H96c6.53,0,10.29,5.54,11.87,11.87l3.82,15.35l2.2-4.14c11.34-0.66,12.35,5.93,0.35,11.62l1.95,2.99c7.89,8.11,7.15,22.45,5.92,42.48v8.14c0,2.04-1.67,3.71-3.71,3.71h-15.83c-2.04,0-3.71-1.67-3.71-3.71v-4.54H24.04v4.54c0,2.04-1.67,3.71-3.71,3.71H4.5c-2.04,0-3.71-1.67-3.71-3.71V78.2c0-0.2,0.02-0.39,0.04-0.58C-0.37,62.25-2.06,42.15,10.17,34.23zM30.38,58.7l-14.06-1.77c-3.32-0.37-4.21,1.03-3.08,3.89l1.52,3.69c0.49,0.95,1.14,1.64,1.9,2.12c0.89,0.55,1.96,0.82,3.15,0.87l12.54,0.1c3.03-0.01,4.34-1.22,3.39-4C34.96,60.99,33.18,59.35,30.38,58.7zM54.38,52.79h14.4c0.85,0,1.55,0.7,1.55,1.55s-0.7,1.55-1.55,1.55h-14.4c-0.85,0-1.55-0.7-1.55-1.55S53.52,52.79,54.38,52.79zM89.96,73.15h14.4c0.85,0,1.55,0.7,1.55,1.55s-0.7,1.55-1.55,1.55h-14.4c-0.85,0-1.55-0.7-1.55-1.55S89.1,73.15,89.96,73.15zM92.5,58.7l14.06-1.77c3.32-0.37,4.21,1.03,3.08,3.89l-1.52,3.69c-0.49,0.95-1.14,1.64-1.9,2.12c-0.89,0.55-1.96,0.82-3.15,0.87l-12.54,0.1c-3.03-0.01-4.34-1.22-3.39-4C87.92,60.99,89.7,59.35,92.5,58.7zM18.41,73.15h14.4c0.85,0,1.55,0.7,1.55,1.55s-0.7,1.55-1.55,1.55h-14.4c-0.85,0-1.55-0.7-1.55-1.55S17.56,73.15,18.41,73.15zM19.23,31.2h86.82l-3.83-15.92c-1.05-4.85-4.07-9.05-9.05-9.05H33.06c-4.97,0-7.52,4.31-9.05,9.05L19.23,31.2z"/>
                    </svg>
                    <span class="text-xl font-bold">DriveNow</span>
                </div>
                <p class="text-gray-400 mb-4">A melhor plataforma para aluguel de carros entre pessoas.</p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                  d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <span class="sr-only">Instagram</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                  d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Links Rápidos</h3>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
                    <li><a href="#como-funciona" class="text-gray-400 hover:text-white transition-colors">Como
                        funciona</a></li>
                    <li><a href="#carros" class="text-gray-400 hover:text-white transition-colors">Veículos</a></li>
                    <li><a href="#depoimentos" class="text-gray-400 hover:text-white transition-colors">Depoimentos</a>
                    </li>
                </ul>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Suporte</h3>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Central de Ajuda</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Política de Privacidade</a>
                    </li>
                    <li><a href="politicas.html" target="_blank" class="text-gray-400 hover:text-white transition-colors">Termos de Uso</a></li>
                    <li><a href="https://wa.me/5541992449277?text=Ol%C3%A1%2C%20gostaria%20de%20saber%20mais%20sobre%20o%20DriveNow%21" class="text-gray-400 hover:text-white transition-colors">Contato</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Contato</h3>
                <ul class="space-y-2">
                    <li class="flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:suporte@drivenow.com.br" class="text-indigo-300 hover:text-indigo-200">suporte@drivenow.com.br</a>
                    </li>
                    <li class="flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        (41) 99244-9277
                    </li>
                    <li class="flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Curitiba, PR - Brasil
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-10 border-t border-gray-800 pt-8 text-center text-gray-400">
            <p>&copy; 2025 DriveNow. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>

<script>
    // Animação de fade-in nos elementos
    document.addEventListener('DOMContentLoaded', function () {
        const fadeElements = document.querySelectorAll('.fade-in');
        fadeElements.forEach(element => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        });
    });

    //  Smooth scroll para links de ancoragem
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    const boxes = document.querySelectorAll(".animate-on-scroll");
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
                observer.unobserve(entry.target);
            }
        });
    }, {threshold: 0.1});

    boxes.forEach(box => observer.observe(box));

    const observer2 = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
                observer2.unobserve(entry.target);
            }
        });
    }, {threshold: 0.1});

    document.querySelectorAll(".animate-on-scroll").forEach(el => observer2.observe(el));
</script>
</body>
<!-- Flowbite JS -->
<script src="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.js"></script>
</html>