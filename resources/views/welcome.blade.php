<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FluffyChat</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            background: #ffe3e6;
        }

        .typing {
            border-right: 3px solid #ff4f87;
            white-space: nowrap;
            overflow: hidden;
            animation: caret 0.7s infinite;
        }

        @keyframes caret {
            50% { border-color: transparent; }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center">

    <div class="bg-white w-[400px] p-10 rounded-[40px] shadow-lg text-center">

        <h1 class="text-3xl font-bold text-rose-500 mb-2">Welcome</h1>

        <!-- TYPING TEXT -->
        <h1 id="typingTitle" class="text-4xl font-serif mb-4 typing"></h1>

       <div class="flex justify-center mb-10">
            <img 
                src="{{ asset('images/logo fluffy.jpg') }}" 
                alt="FluffyChat Logo"
                class="w-28 h-28 rounded-full shadow-md"
            >
        </div>

        <a href="{{ route('login') }}"
           class="block w-full py-3 rounded-full bg-rose-500 text-white text-lg font-semibold shadow hover:bg-rose-600 transition mb-4">
            Login
        </a>

        <a href="{{ route('register') }}"
           class="block w-full py-3 rounded-full border-2 border-rose-500 text-rose-500 text-lg font-semibold hover:bg-rose-600 hover:text-white transition">
            Register
        </a>

    </div>

    <!-- Infinite typing effect -->
    <script>
        const text = "FluffyChat";
        const speed = 120;
        const eraseSpeed = 80;
        const delay = 900;

        let i = 0;
        let isDeleting = false;

        function typeLoop() {
            const title = document.getElementById("typingTitle");

            if (!isDeleting && i < text.length) {
                title.textContent += text.charAt(i);
                i++;
                setTimeout(typeLoop, speed);

            } else if (isDeleting && i > 0) {
                title.textContent = text.substring(0, i - 1);
                i--;
                setTimeout(typeLoop, eraseSpeed);

            } else {
                isDeleting = !isDeleting;
                setTimeout(typeLoop, delay);
            }
        }

        typeLoop();
    </script>

</body>
</html>
