const jobSelect = document.getElementById('jobSelect');
const uploadBtn = document.getElementById('uploadBtn');
const cvInput = document.getElementById('cvInput');
const chatWindow = document.getElementById('chatWindow');
const chatForm = document.getElementById('chatForm');
const chatMessage = document.getElementById('chatMessage');
const sendBtn = document.getElementById('sendBtn');

let sessionId = sessionStorage.getItem('cv_session_id') || '';
let cvReady = false;

function renderMessage(text, role) {
  const bubble = document.createElement('div');
  bubble.className = `message ${role}`;

  if (role === 'assistant') {
    const meta = document.createElement('span');
    meta.className = 'meta';
    meta.textContent = 'Gemini';
    bubble.appendChild(meta);
  }

  bubble.appendChild(document.createTextNode(text));
  chatWindow.appendChild(bubble);
  chatWindow.querySelector('.empty-state')?.remove();
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function renderError(text) {
  const error = document.createElement('div');
  error.className = 'error';
  error.textContent = text;
  chatWindow.appendChild(error);
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

async function loadJobs() {
  try {
    const res = await fetch('api/jobs.php');
    const data = await res.json();
    jobSelect.innerHTML = '';
    if (!data.success || !data.jobs.length) {
      jobSelect.innerHTML = '<option value="">No jobs found</option>';
      return;
    }
    jobSelect.append(new Option('Select a job', ''));
    data.jobs.forEach((job) => {
      jobSelect.append(new Option(job.title, job.id));
    });
  } catch (err) {
    jobSelect.innerHTML = '<option value="">Failed to load jobs</option>';
  }
}

async function uploadCv() {
  if (!cvInput.files.length) {
    alert('Please choose a PDF CV first.');
    return;
  }

  const formData = new FormData();
  formData.append('cv', cvInput.files[0]);

  uploadBtn.disabled = true;
  uploadBtn.textContent = 'Uploading...';

  try {
    const res = await fetch('api/upload_cv.php', {
      method: 'POST',
      body: formData,
    });
    const data = await res.json();

    if (!data.success) {
      throw new Error(data.error || 'Upload failed');
    }

    sessionId = data.session_id;
    sessionStorage.setItem('cv_session_id', sessionId);
    cvReady = true;
    renderMessage('CV uploaded. Ask anything about how you match this job!', 'assistant');
  } catch (err) {
    renderError(err.message);
  } finally {
    uploadBtn.disabled = false;
    uploadBtn.textContent = 'Upload CV';
  }
}

async function fetchHistory() {
  if (!sessionId || !jobSelect.value) {
    return;
  }

  try {
    const res = await fetch('api/chat_history.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_id: sessionId, job_id: parseInt(jobSelect.value, 10) }),
    });
    const data = await res.json();
    if (!data.success || !data.history.length) {
      return;
    }
    chatWindow.innerHTML = '';
    data.history.forEach((entry) => {
      renderMessage(entry.user_message, 'user');
      renderMessage(entry.assistant_response, 'assistant');
    });
  } catch (err) {
    renderError('Could not load past chat.');
  }
}

async function sendMessage(event) {
  event.preventDefault();
  const message = chatMessage.value.trim();

  if (!message) return;

  if (!jobSelect.value) {
    renderError('Select a job before chatting.');
    return;
  }

  if (!cvReady) {
    renderError('Upload your CV first.');
    return;
  }

  renderMessage(message, 'user');
  chatMessage.value = '';
  sendBtn.disabled = true;
  sendBtn.textContent = 'Thinking...';

  try {
    const res = await fetch('api/cv_chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        session_id: sessionId,
        job_id: parseInt(jobSelect.value, 10),
        message,
      }),
    });

    const data = await res.json();
    if (!data.success) {
      throw new Error(data.error || 'Chat failed.');
    }

    sessionId = data.session_id;
    sessionStorage.setItem('cv_session_id', sessionId);
    renderMessage(data.response, 'assistant');
  } catch (err) {
    renderError(err.message);
  } finally {
    sendBtn.disabled = false;
    sendBtn.textContent = 'Send';
    chatWindow.scrollTop = chatWindow.scrollHeight;
  }
}

uploadBtn.addEventListener('click', uploadCv);
chatForm.addEventListener('submit', sendMessage);
jobSelect.addEventListener('change', fetchHistory);

loadJobs();

if (sessionId) {
  cvReady = true;
  fetchHistory();
}
