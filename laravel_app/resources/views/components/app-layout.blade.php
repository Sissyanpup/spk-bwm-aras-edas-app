<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title ?? 'SPK System by Muhammad Zirlda Prairi' }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
        onload="renderMathInElement(document.body);"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 font-sans antialiased">
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <span class="text-xl font-bold text-indigo-600">SPK<span class="text-gray-900">Lab</span></span>
            <a href="/" class="text-sm text-gray-500 hover:text-indigo-600">Reset / Home</a>
        </div>
    </nav>

    <main class="py-10">
        {{ $slot }}
    </main>

    @stack('scripts')
</body>

</html>
