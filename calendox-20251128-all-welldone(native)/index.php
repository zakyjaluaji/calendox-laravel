<?php
// Pemeriksaan sesi: jika belum login, arahkan ke login.php
session_start();
if (empty($_SESSION['username'])) {
  header('Location: login.php');
  exit;
}

include "calendar.php";
// Pemeriksaan status Google
require_once __DIR__ . '/google-config.php';
$googleConnected = false;
$token = loadGoogleToken();
if (is_array($token)) {
  // Dasar: ada access_token atau refresh_token
  $googleConnected = !empty($token['access_token']) || !empty($token['refresh_token']);
  // Validasi lebih lanjut jika library tersedia
  if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    try {
      $client = new Google_Client();
      $client->setClientId(GOOGLE_CLIENT_ID);
      $client->setClientSecret(GOOGLE_CLIENT_SECRET);
      $client->setRedirectUri(GOOGLE_REDIRECT_URI);
      $client->addScope(\Google\Service\Calendar::CALENDAR);
      $client->setAccessToken($token);
      // Connected jika token belum expired atau ada refresh token
      if (!$client->isAccessTokenExpired() || $client->getRefreshToken()) {
        $googleConnected = true;
      }
    } catch (Throwable $e) { /* ignore */
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CalendoX</title>
  <meta name="description" content="My Own Calendar Project">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
  <!-- jQuery UI for Autocomplete -->
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
          Akses: <?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Tamu'; ?> | Google: <?php echo $googleConnected ? 'Connected' : 'Not Connected'; ?>
        </div>
      </div>
      <div class="header-right">
        <button type="button" id="logoutBtn" class="logout-btn" title="Logout">Logout</button>
      </div>
    </div>
  </header>

  <!-- ✅ Success / Error Messages -->
  <?php if ($successMsg): ?>
    <!-- Toast will be shown via JS -->
  <?php elseif ($errorMsg): ?>
    <!-- Toast will be shown via JS -->
  <?php endif; ?>

  <!-- ⏰ Clock disabled
  <div class="clock-container">
    <div id="clock"></div> 
     <h6><a href="./">Calendar</a> | <a href="participant.php">Participant</a></h6>
  </div>
  -->

  <!-- 📅 Calendar -->
  <div class="calendar">
    <div class="nav-btn-container">
      <button onclick="changeMonth(-1)" class="nav-btn">⏮️</button>
      <h2 id="monthYear" style="margin: 0"></h2>
      <button onclick="changeMonth(1)" class="nav-btn">⏭️</button>
    </div>

    <div class="calendar-grid" id="calendar"></div>
  </div>

  <!-- Toast container -->
  <div id="toast-container" class="toast-container"></div>

  <!-- 📌 Modal -->
  <div class="modal" id="eventModal">
    <div class="modal-content">

      <!-- Dropdown Selector -->
      <div id="eventSelectorWrapper" style="display: none;">
        <label for="eventSelector"><strong>Pilih Event:</strong></label>
        <select id="eventSelector" onchange="handleEventSelection(this.value)">
          <option disabled selected>Pilih Event...</option>
        </select>
      </div>

      <!-- 📝 Form -->
      <form method="POST" id="eventForm" enctype="multipart/form-data">
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

        <!-- 🔗 Preview Lampiran -->
        <div id="attachmentPreviewWrapper" style="display:none; margin:8px 0;">
          <a id="attachmentPreviewLink" href="#" target="_blank" rel="noopener">📄 Lihat Lampiran</a>
        </div>

        <button type="submit">💾 Simpan</button>
      </form>

      <!-- 🗑️ Delete -->
      <form method="POST" id="deleteForm">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="event_id" id="deleteEventId">
        <button type="submit" class="submit-btn">🗑️ Hapus</button>
      </form>

      <!-- 🔗 Google Sync Controls dipindah ke modal baru -->

      <!-- ❌ Batal -->
      <button type="button" class="submit-btn" onclick="closeModal()" style="background:#ccc">❌ Batal</button>
    </div>
  </div>

  <!-- 👥 Participant Modal -->
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

  <!-- 👥 Participant Data Modal -->
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
          <tbody>
            <!-- rows injected by JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 🔗 Google Modal -->
  <div class="modal" id="googleModal" style="display:none">
    <div class="modal-content">
      <h3 style="margin-top:0">Integrasi</h3>
      <p style="margin-bottom:12px">Hubungkan akun Google dan sinkronkan.</p>
      <div style="display:flex; flex-direction:column; gap:8px;">
        <a href="google-auth.php?link=1" class="submit-btn" style="text-align:center;background:#f3f4f6;color:#111">🔗 Hubungkan Google</a>
        <button type="button" id="syncGoogleBtn" class="submit-btn" style="background:#10b981;color:#fff">☁️ Sinkron Dua Arah</button>
        <button type="button" id="closeGoogleModalBtn" class="submit-btn" style="background:#ccc">❌ Tutup</button>
        <div id="googleSyncStatus" aria-live="polite" style="margin-top:4px;font-size:12px;color:#374151;min-height:16px"></div>
      </div>
    </div>
  </div>

  <!-- Hapus Konfirmasi Modal -->
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

  <!-- Konfirmasi Hapus Peserta Modal -->
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

  <!-- 🔽 Events JSON from PHP -->


  <!-- 📜 Calendar Logic -->


  <script>
    const events = <?= json_encode($eventsFromDB, JSON_UNESCAPED_UNICODE); ?>;
  </script>

  <script>
    window.APP = {
      isAdmin: <?= json_encode(($_SESSION['level'] ?? '') === 'Admin') ?>
    };
  </script>

  <!-- jQuery core and UI -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

  <script src="calendar.js"></script>

  <!-- Trigger toast for PHP messages -->
  <script>
    (function() {
      try {
        <?php if (!empty($successMsg)): ?>
          if (typeof showToast === 'function') showToast('<?= addslashes($successMsg) ?>', 'success');
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
          if (typeof showToast === 'function') showToast('<?= addslashes($errorMsg) ?>', 'error');
        <?php endif; ?>
      } catch (e) {
        /* ignore */
      }
    })();
  </script>

  <!-- Dark mode toggle + Logout logic -->
  <script>
    (function() {
      const toggle = document.getElementById('darkModeToggle');
      const bodyEl = document.body;
      const saved = localStorage.getItem('darkMode') === 'true';
      if (saved) {
        bodyEl.classList.add('dark');
        if (toggle) toggle.checked = true;
      }
      if (toggle) {
        toggle.addEventListener('change', function() {
          if (this.checked) {
            bodyEl.classList.add('dark');
            localStorage.setItem('darkMode', 'true');
          } else {
            bodyEl.classList.remove('dark');
            localStorage.setItem('darkMode', 'false');
          }
        });
      }

      const logoutBtn = document.getElementById('logoutBtn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
          // Redirect ke endpoint logout
          window.location.href = 'logout.php';
        });
      }
    })();
  </script>

</body>

</html>