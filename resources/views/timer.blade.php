<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>タイマー</title>
        <style>
            
        </style>
</head>
<body>
    <h1 id="time">00:00.000</h1>
    <button id="startBtn">開始</button>
    <button id="saveBtn">登録</button>
    <button id="stopBtn">停止</button>
    <button id="resetBtn">リセット</button>

    <script>
        let elapsedMs = 0;
        let startTime = null;
        let intervalId = null;

        const display = document.getElementById('time');

        function formatTime(ms) {
            const minutes = String(Math.floor(ms / 60000)).padStart(2, '0');
            const seconds = String(Math.floor((ms % 60000) / 1000)).padStart(2, '0');
            const milliseconds = String(ms % 1000).padStart(3, '0');
            return `${minutes}:${seconds}.${milliseconds}`;
        }

        function updateDisplay() {
            const current = startTime ? elapsedMs + (Date.now() - startTime) : elapsedMs;
            display.textContent = formatTime(current);
        }

        document.getElementById('startBtn').addEventListener('click', () => {
            if (startTime) return;
            startTime = Date.now();
            intervalId = setInterval(updateDisplay, 10);
        });

        document.getElementById('stopBtn').addEventListener('click', () => {
            if (!startTime) return;
            elapsedMs += Date.now() - startTime;
            startTime = null;
            clearInterval(intervalId);
        });

        document.getElementById('saveBtn').addEventListener('click', () => {
            if (!startTime) return;
            fetch('/timer/record', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ elapsed_ms: Date.now() - startTime }),
            });
        });

        document.getElementById('resetBtn').addEventListener('click', () => {
            elapsedMs = 0;
            startTime = null;
            clearInterval(intervalId);
            updateDisplay();

            fetch('/timer/record', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
        });
    </script>
</body>
</html>