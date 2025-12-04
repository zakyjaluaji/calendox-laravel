<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>CalendoX</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
  <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
</head>
<body>
  <header>
    <div class="header-bar">
      <div class="header-left">
        <label class="switch" title="Dark mode">
          <input type="checkbox" id="darkModeToggle">
          <span class="slider"></span>
        </label>
      </div>
      <div class="header-center">
        <h1 style="margin:0">CalendoX</h1>
        <div class="role-label" title="Peran pengguna">
          Akses: {{ $role ?? 'Tamu' }} | Google: {{ ($googleConnected ?? false) ? 'Connected' : 'Not Connected' }}
        </div>
      </div>
      <div class="header-right">
        <button type="button" id="logoutBtn" class="logout-btn" title="Logout">Logout</button>
      </div>
    </div>
  </header>

  <div class="calendar">
    <div class="nav-btn-container">
      <button onclick="changeMonth(-1)" class="nav-btn">⏮️</button>
      <h2 id="monthYear" style="margin: 0"></h2>
      <button onclick="changeMonth(1)" class="nav-btn">⏭️</button>
    </div>
    <div class="calendar-grid" id="calendar"></div>
  </div>

  <div id="toast-container" class="toast-container"></div>

  <div class="modal" id="eventModal">
    <div class="modal-content">
      <div id="eventSelectorWrapper" style="display: none;">
        <label for="eventSelector"><strong>Pilih Event:</strong></label>
        <select id="eventSelector" onchange="handleEventSelection(this.value)">
          <option disabled selected>Pilih Event...</option>
        </select>
      </div>

      <form method="POST" id="eventForm" enctype="multipart/form-data" action="/appointments">
        @csrf
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="event_id" id="eventId">

        <label for="titleName">Judul:</label>
        <input type="text" name="title" id="titleName" required>

        <label for="picName">Nama PIC:</label>
        <input type="text" name="pic_name" id="picName" required>

        <label for="startDate">Tanggal Mulai:</label>
        <input type="date" name="start_date" id="startDate" required>

        <label for="endDate">Tanggal Selesai:</label>
        <input type="date" name="end_date" id="endDate" required>

        <label for="startTime">Waktu Mulai:</label>
        <input type="time" name="start_time" id="startTime" required>

        <label for="endTime">Waktu Selesai:</label>
        <input type="time" name="end_time" id="endTime" required>

        <label for="eventColor">Warna Event:</label>
        <select name="color" id="eventColor">
          <option value="#3b82f6" style="background:#3b82f6;color:#fff">Blue</option>
          <option value="#1e3a8a" style="background:#1e3a8a;color:#fff">Blue Dark</option>
          <option value="#6366f1" style="background:#6366f1;color:#fff">Indigo</option>
          <option value="#8b5cf6" style="background:#8b5cf6;color:#fff">Violet</option>
          <option value="#14b8a6" style="background:#14b8a6;color:#fff">Teal</option>
          <option value="#10b981" style="background:#10b981;color:#fff">Emerald</option>
          <option value="#f59e0b" style="background:#f59e0b;color:#111">Amber</option>
          <option value="#f43f5e" style="background:#f43f5e;color:#fff">Rose</option>
          <option value="#ef4444" style="background:#ef4444;color:#fff">Red</option>
          <option value="#64748b" style="background:#64748b;color:#fff">Slate</option>
        </select>

        <label for="attachment">Lampiran (PDF, maks 5MB, Opsional):</label>
        <input type="file" name="attachment" id="attachment" accept="application/pdf,.pdf">

        <div id="attachmentPreviewWrapper" style="display:none; margin:8px 0;">
          <a id="attachmentPreviewLink" href="#" target="_blank" rel="noopener">📄 Lihat Lampiran</a>
        </div>

        <button type="submit">💾 Simpan</button>
      </form>

      <form method="POST" id="deleteForm" action="/appointments/0/delete">
        @csrf
        <input type="hidden" name="event_id" id="deleteEventId">
        <button type="submit" class="submit-btn">🗑️ Hapus</button>
      </form>

      <button type="button" class="submit-btn" onclick="closeModal()" style="background:#ccc">❌ Batal</button>
    </div>
  </div>

  <div class="modal" id="participantModal">
    <div class="modal-content">
      <label for="participantEventSelector">Pilih Event:</label>
      <select id="participantEventSelector">
        <option disabled selected>Pilih event...</option>
      </select>

      <label for="participantName">Peserta:</label>
      <input type="text" id="participantName" placeholder="Ketik nama/email untuk mencari" autocomplete="off" />
      <input type="hidden" id="participantEmail" />

      <button type="button" id="saveParticipantBtn">💾 Simpan Peserta</button>
      <button type="button" id="closeParticipantModalBtn" class="submit-btn" style="background:#ccc">❌ Batal</button>
    </div>
  </div>

  <div class="modal" id="participantDataModal" style="display:none">
    <div class="modal-content">
      <label for="participantDataEventSelector">Pilih Event:</label>
      <select id="participantDataEventSelector"></select>
      <div class="modal-actions">
        <button type="button" id="openManageParticipantBtn" class="submit-btn">➕ Tambah Peserta</button>
        <button type="button" id="closeParticipantDataModalBtn" class="submit-btn" style="background:#ccc">❌ Tutup</button>
      </div>
      <div class="table-wrapper">
        <table id="participantDataTable" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="background:#f3f4f6">
              <th style="text-align:left; padding:8px; border:1px solid #e5e7eb">Nama</th>
              <th style="text-align:left; padding:8px; border:1px solid #e5e7eb">Email</th>
              <th style="text-align:left; padding:8px; border:1px solid #e5e7eb">Dibuat</th>
              <th style="text-align:left; padding:8px; border:1px solid #e5e7eb">Aksi</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal" id="googleModal" style="display:none">
    <div class="modal-content">
      <h3 style="margin-top:0">Integrasi</h3>
      <p style="margin-bottom:12px">Hubungkan akun Google dan sinkronkan.</p>
      <div style="display:flex; flex-direction:column; gap:8px;">
        <a href="/google/auth" class="submit-btn" style="text-align:center;background:#f3f4f6;color:#111">🔗 Hubungkan Google</a>
        <button type="button" id="syncGoogleBtn" class="submit-btn" style="background:#10b981;color:#fff">☁️ Sinkron Dua Arah</button>
        <button type="button" id="closeGoogleModalBtn" class="submit-btn" style="background:#ccc">❌ Tutup</button>
        <div id="googleSyncStatus" aria-live="polite" style="margin-top:4px;font-size:12px;color:#374151;min-height:16px"></div>
      </div>
    </div>
  </div>

  <div class="modal" id="deleteConfirmModal" style="display:none">
    <div class="modal-content">
      <h3 style="margin-top:0">Konfirmasi Hapus</h3>
      <p>Apakah Anda yakin ingin menghapus appointment ini?</p>
      <div style="display:flex; gap:8px; margin-top:12px">
        <button type="button" id="confirmDeleteBtn" class="submit-btn" style="background:#ef4444;color:#fff">OK</button>
        <button type="button" id="cancelDeleteBtn" class="submit-btn" style="background:#ccc">Batal</button>
      </div>
    </div>
  </div>

  <div class="modal" id="participantDeleteConfirmModal" style="display:none">
    <div class="modal-content">
      <h3 style="margin-top:0">Konfirmasi Hapus Peserta</h3>
      <p>Apakah Anda yakin ingin menghapus peserta ini?</p>
      <div style="display:flex; gap:8px; margin-top:12px">
        <button type="button" id="confirmDeleteParticipantBtn" class="submit-btn" style="background:#ef4444;color:#fff">OK</button>
        <button type="button" id="cancelDeleteParticipantBtn" class="submit-btn" style="background:#ccc">Batal</button>
      </div>
    </div>
  </div>

  <div class="modal" id="participantInviteConfirmModal" style="display:none">
    <div class="modal-content">
      <h3 style="margin-top:0">Kirim Undangan Email</h3>
      <p>Apakah Anda ingin mengirim undangan Google Calendar untuk peserta baru?</p>
      <div style="display:flex; gap:8px; margin-top:12px">
        <button type="button" id="confirmInviteParticipantBtn" class="submit-btn" style="background:#10b981;color:#fff">Kirim</button>
        <button type="button" id="cancelInviteParticipantBtn" class="submit-btn" style="background:#ccc">Batal</button>
      </div>
    </div>
  </div>

  <script>
    const events = @json($eventsFromDB);
    window.APP = { isAdmin: @json($isAdmin ?? false) };
  </script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
  <script src="{{ asset('js/calendar.js') }}"></script>
</body>
</html>
