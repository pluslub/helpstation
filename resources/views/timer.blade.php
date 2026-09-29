<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>タイマー</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f4;
        }
        #display {
            font-size: 64px;
            font-variant-numeric: tabular-nums;
            margin-bottom: 24px;
        }
        .buttons button {
            font-size: 18px;
            padding: 10px 24px;
            margin: 0 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="display">00:00:00</div>
    <div class="buttons">
        <button id="startBtn">開始</button>
        <button id="stopBtn">停止</button>
        <button id="resetBtn">リセット</button>
    </div>

    <script>
        let elapsedMs = 0;
        let startTime = null;
        let intervalId = null;

        const display = document.getElementById('display');
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const resetBtn = document.getElementById('resetBtn');

        function formatTime(ms) {
            const totalSeconds = Math.floor(ms / 1000);
            const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            return `${hours}:${minutes}:${seconds}`;
        }

        function updateDisplay() {
            const current = startTime ? elapsedMs + (Date.now() - startTime) : elapsedMs;
            display.textContent = formatTime(current);
        }

        startBtn.addEventListener('click', () => {
            if (startTime) return;
            startTime = Date.now();
            intervalId = setInterval(updateDisplay, 100);
        });

        stopBtn.addEventListener('click', () => {
            if (!startTime) return;
            elapsedMs += Date.now() - startTime;
            startTime = null;
            clearInterval(intervalId);
        });

        resetBtn.addEventListener('click', () => {
            elapsedMs = 0;
            startTime = null;
            clearInterval(intervalId);
            updateDisplay();
        });
    </script>
</body>
</html>
