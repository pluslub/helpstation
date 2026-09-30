<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <title>タイマー</title>
    <style>
        body {
            font-family: "Helvetica Neue",
                Arial,
                "Hiragino Kaku Gothic ProN",
                "Hiragino Sans",
                "Noto Sans JP",
                sans-serif;
            position: relative;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f4;
        }

        .timer-section {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        #time {
            font-size: 96px;
            font-family: 'IBM Plex Mono';
            font-variant-numeric: tabular-nums;
            text-align: center;
            margin-bottom: 24px;
        }

        .buttons {
            display: flex;
            gap:30px;
            margin-bottom: 24px;
        }

        button {
            font-size: 18px;
            padding: 10px 24px;
            cursor: pointer;
        }

        .records-section {
            position: absolute;
            top: 40px;
            right: 40px;
        }

        #records {
            list-style: none;
            font-size: 18px;
            font-family: 'IBM Plex Mono';
            padding: 0;
            width: 300px;
        }

        #records li {
            padding: 8px 16px;
            margin-bottom: 4px;
            border-radius: 4px;
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body>
    <div class="timer-section">
        <h1 id="time">00:00.000</h1>
        <div class="buttons">
            <button id="startBtn">開始</button>
            <button id="saveBtn">登録</button>
            <button id="stopBtn">停止</button>
            <button id="resetBtn">リセット</button>
        </div>
    </div>
    <div class="records-section">
        <h2>記録一覧</h2>
        <ul id="records"></ul>
    </div>


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

        function refreshRecords() {
        fetch('/timer/records')
            .then(response => response.json())
            .then(records => {
                const list = document.getElementById('records');
                list.innerHTML = '';
                records.forEach(record => {
                    const li = document.createElement('li');
                    console.log(`${record.elapsed_ms}`)
                    li.textContent = formatTime(`${record.elapsed_ms}`);
                    list.appendChild(li);
                });
            });
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
            const current = startTime ? elapsedMs + (Date.now() - startTime) : elapsedMs;
            fetch('/timer/record', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ elapsed_ms: current}),
            })
            .then(() => refreshRecords());
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
            })
            .then(() => refreshRecords());
        });

        refreshRecords();
    </script>
</body>
</html>
