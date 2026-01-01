<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI CV Matcher & Chat</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-shell">
        <header class="app-header">
            <div>
                <p class="eyebrow">AI CV Matcher & Chat</p>
                <h1>Match your CV to the right role</h1>
                <p class="subhead">Upload your CV, pick a job, and chat with an AI recruiter powered by Gemini.</p>
            </div>
            <div class="badge">Gemini 2.5 Flash</div>
        </header>

        <section class="controls">
            <div class="control">
                <label for="jobSelect">Select job position</label>
                <select id="jobSelect">
                    <option value="">Loading jobs...</option>
                </select>
            </div>
            <div class="control">
                <label for="cvInput">Upload PDF CV</label>
                <div class="file-row">
                    <input type="file" id="cvInput" accept="application/pdf">
                    <button id="uploadBtn" class="primary">Upload CV</button>
                </div>
                <p class="help">PDF only. We extract text temporarily and never store the file.</p>
            </div>
        </section>

        <section class="chat">
            <div id="chatWindow" class="chat-window">
                <div class="empty-state">
                    <p>Upload your CV and pick a job to start chatting.</p>
                </div>
            </div>
            <form id="chatForm" class="chat-input" autocomplete="off">
                <textarea id="chatMessage" rows="2" placeholder="Ask how your CV matches this job..." required></textarea>
                <button type="submit" class="primary" id="sendBtn">Send</button>
            </form>
        </section>
    </div>

    <script src="assets/js/chat.js"></script>
</body>
</html>
