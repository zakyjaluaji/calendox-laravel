const calendarEl = document.getElementById("calendar");
const monthYearEl = document.getElementById("monthYear");
const modalEl = document.getElementById("eventModal");
const participantModalEl = document.getElementById("participantModal");
const googleModalEl = document.getElementById("googleModal");
const participantDataModalEl = document.getElementById("participantDataModal");
let currentParticipantDataEvents = [];
let currentDate = new Date();

// 🎨 Helper: apply selected color to the dropdown background for better UX
function applyColorToSelect(sel) {
  if (!sel || !sel.value) return;
  const hex = sel.value.trim();
  sel.style.backgroundColor = hex;
  sel.style.color = getContrastColor(hex);
}

function getContrastColor(hex) {
  const h = hex.replace('#', '');
  let r, g, b;
  if (h.length === 3) {
    r = parseInt(h[0] + h[0], 16);
    g = parseInt(h[1] + h[1], 16);
    b = parseInt(h[2] + h[2], 16);
  } else {
    r = parseInt(h.substring(0, 2), 16);
    g = parseInt(h.substring(2, 4), 16);
    b = parseInt(h.substring(4, 6), 16);
  }
  const y = 0.299 * r + 0.587 * g + 0.114 * b; // luminance
  return y > 186 ? '#111' : '#fff';
}

// Sinkronisasikan tampilan combobox warna saat user memilih
const eventColorSelectEl = document.getElementById("eventColor");
if (eventColorSelectEl) {
  // Inisialisasi tampilan sesuai nilai awal
  applyColorToSelect(eventColorSelectEl);
  // Perbarui tampilan saat pilihan berubah
  eventColorSelectEl.addEventListener("change", (e) => applyColorToSelect(e.target));
}

// 🔔 Simple toast helper (3s)
function showToast(message, type = "info") {
  const container = document.getElementById("toast-container");
  if (!container || !message) return;
  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span class="toast-msg">${message}</span>`;
  container.appendChild(toast);
  // animate in
  requestAnimationFrame(() => toast.classList.add("show"));
  // auto dismiss after 3s
  setTimeout(() => {
    toast.classList.remove("show");
    toast.addEventListener("transitionend", () => toast.remove(), { once: true });
  }, 3000);
}

// 📅 Generate Full Calendar View
function renderCalendar(date = new Date()) {
  calendarEl.innerHTML = "";

  const year = date.getFullYear();
  const month = date.getMonth();
  const today = new Date();

  const totalDays = new Date(year, month + 1, 0).getDate();
  const firstDayOfMonth = new Date(year, month, 1).getDay();

  monthYearEl.textContent = date.toLocaleDateString("en-US", {
    month: "long",
    year: "numeric",
  });

  const weekDays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  weekDays.forEach((day) => {
    const dayEl = document.createElement("div");
    dayEl.className = "day-name";
    dayEl.textContent = day;
    calendarEl.appendChild(dayEl);
  });

  for (let i = 0; i < firstDayOfMonth; i++) {
    calendarEl.appendChild(document.createElement("div"));
  }

  for (let day = 1; day <= totalDays; day++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

    const cell = document.createElement("div");
    cell.className = "day";

    if (
      day === today.getDate() &&
      month === today.getMonth() &&
      year === today.getFullYear()
    ) {
      cell.classList.add("today");
    }

    const dateEl = document.createElement("div");
    dateEl.className = "date-number";
    dateEl.textContent = day;
    cell.appendChild(dateEl);

    const eventsToday = events.filter((e) => e.date === dateStr);
    const eventBox = document.createElement("div");
    eventBox.className = "events";

    eventsToday.forEach((event) => {
      const ev = document.createElement("div");
      ev.className = "event";
      // Jika ada warna dari DB/user, override
      if (event.color) {
        ev.style.backgroundColor = event.color;
      }

      const courseEl = document.createElement("div");
      courseEl.className = "course";
      courseEl.textContent = event.title.split(" - ")[0];

      const instructorEl = document.createElement("div");
      instructorEl.className = "instructor";
      instructorEl.textContent = "🧑🏻‍🏫 " + event.title.split(" - ")[1];

      const timeEl = document.createElement("div");
      timeEl.className = "time";
      timeEl.textContent = `⏰ ${event.start_time} - ${event.end_time}`;

      ev.appendChild(courseEl);
      ev.appendChild(instructorEl);
      ev.appendChild(timeEl);
      eventBox.appendChild(ev);
    });

    // ➕ ➖ Overlay Buttons
    const overlay = document.createElement("div");
    overlay.className = "day-overlay";

    if (window.APP && window.APP.isAdmin) {
    const addBtn = document.createElement("button");
    addBtn.className = "overlay-btn";
    addBtn.textContent = "Tambah";
    addBtn.onclick = (e) => {
      e.stopPropagation();
      openModalForAdd(dateStr);
    };
    overlay.appendChild(addBtn);

   
    if (eventsToday.length >= 1) {
      const editBtn = document.createElement("button");
      editBtn.className = "overlay-btn";
      editBtn.textContent = "Ubah";
      editBtn.onclick = (e) => {
        e.stopPropagation();
        openModalForEdit(eventsToday);
      };
      overlay.appendChild(editBtn);

      // Hari dengan ≥1 event: tampilkan seluruh tombol overlay
      const participantsBtn = document.createElement("button");
      participantsBtn.className = "overlay-btn";
      participantsBtn.textContent = "Peserta";
      participantsBtn.onclick = (e) => {
        e.stopPropagation();
        openParticipantDataModal(eventsToday);
      };
      overlay.appendChild(participantsBtn);

      const syncBtn = document.createElement("button");
      syncBtn.className = "overlay-btn";
      syncBtn.textContent = "Sinkron";
      syncBtn.onclick = (e) => {
        e.stopPropagation();
        if (googleModalEl) {
          googleModalEl.style.display = "flex";
        } else {
          showToast("Modal Google tidak tersedia.", "error");
        }
      };
      overlay.appendChild(syncBtn);
    }
  }

    cell.appendChild(overlay);
    cell.appendChild(eventBox);
    calendarEl.appendChild(cell);
  }
}

// ✅ Add Event Modal
function openModalForAdd(dateStr) {
  document.getElementById("formAction").value = "add";
  document.getElementById("eventId").value = "";
  document.getElementById("deleteEventId").value = "";
  document.getElementById("titleName").value = "";
  document.getElementById("picName").value = "";
  document.getElementById("startDate").value = dateStr;
  document.getElementById("endDate").value = dateStr;
  document.getElementById("startTime").value = "09:00";
  document.getElementById("endTime").value = "10:00";
  // Default color to brand blue
  const colorSel = document.getElementById("eventColor");
  if (colorSel) {
    colorSel.value = "#3b82f6";
    applyColorToSelect(colorSel);
  }

  // Sembunyikan preview lampiran saat mode tambah
  const previewWrapper = document.getElementById("attachmentPreviewWrapper");
  if (previewWrapper) previewWrapper.style.display = "none";

  const selector = document.getElementById("eventSelector");
  const wrapper = document.getElementById("eventSelectorWrapper");
  if (selector && wrapper) {
    selector.innerHTML = "";
    wrapper.style.display = "none";
  }

  modalEl.style.display = "flex";
}

// ✏️ Edit Event Modal
function openModalForEdit(eventsOnDate) {
  document.getElementById("formAction").value = "edit";
  modalEl.style.display = "flex";

  const selector = document.getElementById("eventSelector");
  const wrapper = document.getElementById("eventSelectorWrapper");

    selector.innerHTML = "<option disabled selected>Pilih event...</option>";

  eventsOnDate.forEach((e) => {
    const option = document.createElement("option");
    option.value = JSON.stringify(e);
    option.textContent = `${e.title} (${e.start} ➡️ ${e.end})`;
    selector.appendChild(option);
  });

  if (eventsOnDate.length > 1) {
    wrapper.style.display = "block";
  } else {
    wrapper.style.display = "none";
  }

  handleEventSelection(JSON.stringify(eventsOnDate[0]));
}

// ⬇️ Autofill the Form
function handleEventSelection(eventJSON) {
  const event = JSON.parse(eventJSON);

  document.getElementById("eventId").value = event.id;
  document.getElementById("deleteEventId").value = event.id;

  const [course, instructor] = event.title.split(" - ").map((e) => e.trim());

  document.getElementById("titleName").value = course || "";
  document.getElementById("picName").value = instructor || "";
  document.getElementById("startDate").value = event.start || "";
  document.getElementById("endDate").value = event.end || "";
  document.getElementById("startTime").value = event.start_time || "";
  document.getElementById("endTime").value = event.end_time || "";

  // Set selected color from event or default
  const colorSel = document.getElementById("eventColor");
  if (colorSel) {
    colorSel.value = event.color || "#3b82f6";
    applyColorToSelect(colorSel);
  }

  // Tampilkan link preview jika tersedia
  const previewWrapper = document.getElementById("attachmentPreviewWrapper");
  const previewLink = document.getElementById("attachmentPreviewLink");
  if (previewWrapper && previewLink) {
    const url = event.attachment_url || "";
    if (url) {
      previewWrapper.style.display = "block";
      previewLink.href = url;
      previewLink.textContent = "📄 Lihat Lampiran";
    } else {
      previewWrapper.style.display = "none";
      previewLink.removeAttribute("href");
    }
  }
}

// ❌ Close the Modal
function closeModal() {
  modalEl.style.display = "none";
}

// 👥 Participant Modal Logic
function openParticipantModal(eventsOnDate = []) {
  const selector = document.getElementById("participantEventSelector");
  const nameInput = document.getElementById("participantName");

  // reset form
  nameInput.value = "";
  selector.innerHTML = "";
  if (eventsOnDate && eventsOnDate.length > 0) {
    const placeholder = document.createElement("option");
    placeholder.disabled = true;
    placeholder.selected = true;
    placeholder.textContent = "Pilih event...";
    selector.appendChild(placeholder);

    eventsOnDate.forEach((e) => {
      const option = document.createElement("option");
      option.value = e.id;
      option.textContent = `${e.title} (${e.start} ➡️ ${e.end})`;
      selector.appendChild(option);
    });
  } else {
    const noEvent = document.createElement("option");
    noEvent.disabled = true;
    noEvent.selected = true;
    noEvent.textContent = "Tidak ada event di tanggal ini";
    selector.appendChild(noEvent);
  }

  participantModalEl.style.display = "flex";
}

function closeParticipantModal() {
  participantModalEl.style.display = "none";
}

// 👥 Participant Data Modal (read-only listing)
function openParticipantDataModal(eventsOnDate = []) {
  if (!participantDataModalEl) {
    showToast("Modal Data Peserta tidak tersedia.", "error");
    return;
  }

  currentParticipantDataEvents = Array.isArray(eventsOnDate) ? eventsOnDate : [];

  const selector = document.getElementById("participantDataEventSelector");
  const tbody = document.querySelector("#participantDataTable tbody");
  if (!selector || !tbody) {
    showToast("Elemen Data Peserta tidak ditemukan.", "error");
    return;
  }

  selector.innerHTML = "";
  tbody.innerHTML = "";

  if (!currentParticipantDataEvents.length) {
    showToast("Tidak ada event pada tanggal ini.", "info");
    return;
  }

  if (currentParticipantDataEvents.length === 1) {
    const e = currentParticipantDataEvents[0];
    const opt = document.createElement("option");
    opt.value = e.id;
    opt.textContent = `${e.title} (${e.start} ➡️ ${e.end})`;
    opt.selected = true;
    selector.appendChild(opt);
    participantDataModalEl.style.display = "flex";
    fetchParticipants(e.id);
    return;
  }

  const placeholder = document.createElement("option");
  placeholder.disabled = true;
  placeholder.selected = true;
  placeholder.textContent = "Pilih event...";
  selector.appendChild(placeholder);

  currentParticipantDataEvents.forEach((e) => {
    const option = document.createElement("option");
    option.value = e.id;
    option.textContent = `${e.title} (${e.start} ➡️ ${e.end})`;
    selector.appendChild(option);
  });

  participantDataModalEl.style.display = "flex";
}

function closeParticipantDataModal() {
  if (!participantDataModalEl) return;
  participantDataModalEl.style.display = "none";
}

function fetchParticipants(appointmentId) {
  const tbody = document.querySelector("#participantDataTable tbody");
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="4" style="padding:8px;border:1px solid #e5e7eb">Memuat...</td></tr>';

  const form = new URLSearchParams();
  form.set("action", "list_participants");
  form.set("appointment_id", String(appointmentId));

  fetch("participants.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: form.toString(),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data && data.success) {
        renderParticipants(Array.isArray(data.data) ? data.data : []);
      } else {
        showToast(data.message || "Gagal memuat data peserta.", "error");
        renderParticipants([]);
      }
    })
    .catch((err) => {
      console.error(err);
      showToast("Terjadi kesalahan saat memuat data peserta.", "error");
      renderParticipants([]);
    });
}

function renderParticipants(rows) {
  const tbody = document.querySelector("#participantDataTable tbody");
  if (!tbody) return;
  tbody.innerHTML = "";

  if (!rows || rows.length === 0) {
    const tr = document.createElement("tr");
    const td = document.createElement("td");
    td.colSpan = 4;
    td.textContent = "Belum ada peserta untuk event ini.";
    td.style.padding = "8px";
    td.style.border = "1px solid #e5e7eb";
    tr.appendChild(td);
    tbody.appendChild(tr);
    return;
  }

  rows.forEach((r) => {
    const tr = document.createElement("tr");
    ["name", "email", "created_at"].forEach((key) => {
      const td = document.createElement("td");
      td.textContent = r[key] || "";
      td.style.padding = "8px";
      td.style.border = "1px solid #e5e7eb";
      tr.appendChild(td);
    });
    const tdAct = document.createElement("td");
    tdAct.style.padding = "8px";
    tdAct.style.border = "1px solid #e5e7eb";
    const delBtn = document.createElement("button");
    delBtn.className = "icon-btn";
    delBtn.textContent = "🗑️";
    delBtn.title = "Hapus peserta";
    delBtn.addEventListener("click", () => {
      pendingDeleteParticipantId = r.id;
      if (participantDeleteConfirmModal) {
        participantDeleteConfirmModal.style.display = "flex";
      }
    });
    tdAct.appendChild(delBtn);
    tr.appendChild(tdAct);
    tbody.appendChild(tr);
  });
}

// Wire Participant Data modal controls
const participantDataSelectorEl = document.getElementById("participantDataEventSelector");
if (participantDataSelectorEl) {
  participantDataSelectorEl.addEventListener("change", (e) => {
    const appointmentId = e.target.value;
    if (appointmentId) fetchParticipants(appointmentId);
  });
}

const closeParticipantDataModalBtn = document.getElementById("closeParticipantDataModalBtn");
if (closeParticipantDataModalBtn) {
  closeParticipantDataModalBtn.addEventListener("click", closeParticipantDataModal);
}

const openManageParticipantBtn = document.getElementById("openManageParticipantBtn");
if (openManageParticipantBtn) {
  openManageParticipantBtn.addEventListener("click", () => {
    const selector = document.getElementById("participantDataEventSelector");
    const selectedId = selector && selector.value ? selector.value : null;
    openParticipantModal(currentParticipantDataEvents);
    if (selectedId) {
      const manageSelector = document.getElementById("participantEventSelector");
      if (manageSelector) manageSelector.value = selectedId;
    }
  });
}

// 🔄 Navigate Between Months
function changeMonth(offset) {
  currentDate.setMonth(currentDate.getMonth() + offset);
  renderCalendar(currentDate);
}

// ⏰ Update the Clock
function updateClock() {
  const now = new Date();
  const clock = document.getElementById("clock");
  if (!clock) return;
  clock.textContent = [
    now.getHours().toString().padStart(2, "0"),
    now.getMinutes().toString().padStart(2, "0"),
    now.getSeconds().toString().padStart(2, "0"),
  ].join(":");
}

// 🚀 Run on Page Load
renderCalendar(currentDate);
const clockEl = document.getElementById("clock");
if (clockEl) {
  updateClock();
  setInterval(updateClock, 1000);
}

// 🗑️ Delete Confirmation Modal Wiring
const deleteForm = document.getElementById("deleteForm");
const deleteConfirmModal = document.getElementById("deleteConfirmModal");
const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");

if (deleteForm && deleteConfirmModal) {
  deleteForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const idInput = document.getElementById("deleteEventId");
    const idVal = idInput ? idInput.value : "";
    if (!idVal) {
      showToast("Pilih event dulu sebelum hapus.", "info");
      return;
    }
    deleteConfirmModal.style.display = "flex";
  });
}

if (confirmDeleteBtn && deleteForm && deleteConfirmModal) {
  confirmDeleteBtn.addEventListener("click", () => {
    deleteConfirmModal.style.display = "none";
    // Submit the original delete form to trigger server-side deletion
    deleteForm.submit();
  });
}

// 👥 Konfirmasi Hapus Peserta
let pendingDeleteParticipantId = null;
const participantDeleteConfirmModal = document.getElementById("participantDeleteConfirmModal");
const confirmDeleteParticipantBtn = document.getElementById("confirmDeleteParticipantBtn");
const cancelDeleteParticipantBtn = document.getElementById("cancelDeleteParticipantBtn");

if (confirmDeleteParticipantBtn && participantDeleteConfirmModal) {
  confirmDeleteParticipantBtn.addEventListener("click", () => {
    participantDeleteConfirmModal.style.display = "none";
    const pid = pendingDeleteParticipantId;
    if (!pid) {
      showToast("Peserta tidak dipilih.", "info");
      return;
    }

    const body = new URLSearchParams();
    body.set("action", "delete_participant");
    body.set("participant_id", String(pid));

    fetch("participants.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body.toString(),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data && data.success) {
          showToast(data.message || "Peserta dihapus.", "success");
          const selector = document.getElementById("participantDataEventSelector");
          const selectedId = selector && selector.value ? selector.value : null;
          if (selectedId) fetchParticipants(selectedId);
        } else {
          showToast(data.message || "Gagal menghapus peserta.", "error");
        }
      })
      .catch((err) => {
        console.error(err);
        showToast("Terjadi kesalahan saat menghapus peserta.", "error");
      })
      .finally(() => {
        pendingDeleteParticipantId = null;
      });
  });
}

if (cancelDeleteParticipantBtn && participantDeleteConfirmModal) {
  cancelDeleteParticipantBtn.addEventListener("click", () => {
    pendingDeleteParticipantId = null;
    participantDeleteConfirmModal.style.display = "none";
  });
}

if (cancelDeleteBtn && deleteConfirmModal) {
  cancelDeleteBtn.addEventListener("click", () => {
    deleteConfirmModal.style.display = "none";
  });
}

const saveParticipantBtn = document.getElementById("saveParticipantBtn");
if (saveParticipantBtn) {
  saveParticipantBtn.addEventListener("click", () => {
    const selector = document.getElementById("participantEventSelector");
    const nameInput = document.getElementById("participantName");
    const emailInput = document.getElementById("participantEmail");
    const eventId = selector.value;
    const participantName = (nameInput.value || "").trim();
    const participantEmail = (emailInput && emailInput.value ? emailInput.value : "").trim();

    if (!eventId || !participantName) {
      alert("Mohon pilih event dan isi nama peserta.");
      return;
    }

    // Simpan ke server
    const btn = saveParticipantBtn;
    btn.disabled = true;
    btn.textContent = "Menyimpan...";

    const body = new URLSearchParams();
    body.set("action", "add_participant");
    body.set("event_id", eventId);
    body.set("name", participantName);
    if (participantEmail) body.set("email", participantEmail);

    fetch("participants.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body.toString(),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data && data.success) {
          showToast(data.message || "Peserta berhasil disimpan.", "success");
          closeParticipantModal();
          // Reload daftar peserta di modal Data Peserta jika ada event terpilih
          try {
            const dataSelector = document.getElementById("participantDataEventSelector");
            const selectedDataEventId = dataSelector && dataSelector.value ? dataSelector.value : null;
            const targetEventId = selectedDataEventId || eventId;
            if (targetEventId) fetchParticipants(targetEventId);
          } catch (e) {
            console.warn("Reload Data Peserta gagal:", e);
          }
        } else {
          showToast(data.message || "Gagal menyimpan peserta.", "error");
        }
      })
      .catch((err) => {
        console.error(err);
        showToast("Terjadi kesalahan saat menyimpan peserta.", "error");
      })
      .finally(() => {
        btn.disabled = false;
        btn.textContent = "💾 Simpan Peserta";
      });
  });
}

const closeParticipantBtn = document.getElementById("closeParticipantModalBtn");
if (closeParticipantBtn) {
  closeParticipantBtn.addEventListener("click", closeParticipantModal);
}

// Sync to Google button
const syncGoogleBtn = document.getElementById("syncGoogleBtn");
if (syncGoogleBtn) {
  syncGoogleBtn.addEventListener("click", () => {
    // Sinkron dua arah tanpa pemilihan event: gunakan rentang -1/+1 bulan (server-side)
    syncGoogleBtn.disabled = true;
    syncGoogleBtn.textContent = "Menyinkronkan...";
    fetch("sync_two_way.php", { method: "POST" })
      .then((res) => res.json())
      .then((data) => {
        if (data && data.success) {
          const msg = data.message || "Sinkron dua arah selesai.";
          const pushed = typeof data.pushed_count === "number" ? `, lokal→Google: ${data.pushed_count}` : "";
          const imported = typeof data.imported === "number" ? `, Google→lokal baru: ${data.imported}` : "";
          const partImp = typeof data.participants_imported === "number" ? `, peserta baru: ${data.participants_imported}` : "";
          showToast(`${msg}${pushed}${imported}${partImp}`, "success");
        } else {
          showToast(data.message || "Sinkron dua arah gagal.", "error");
        }
      })
      .catch((err) => {
        console.error(err);
        showToast("Terjadi kesalahan saat sinkron dua arah.", "error");
      })
      .finally(() => {
        syncGoogleBtn.disabled = false;
        syncGoogleBtn.textContent = "☁️ Sinkron Dua Arah";
      });
  });
}

// Open/Close Google Modal
const openGoogleModalBtn = document.getElementById("openGoogleModalBtn");
const closeGoogleModalBtn = document.getElementById("closeGoogleModalBtn");
if (openGoogleModalBtn && googleModalEl) {
  openGoogleModalBtn.addEventListener("click", () => {
    googleModalEl.style.display = "flex";
  });
}
if (closeGoogleModalBtn && googleModalEl) {
  closeGoogleModalBtn.addEventListener("click", () => {
    googleModalEl.style.display = "none";
  });
}

// jQuery UI Autocomplete for participantName
try {
  if (window.$) {
    const $name = $("#participantName");
    if ($name && $name.length) {
      $name.autocomplete({
        source: function(request, response) {
          $.ajax({
            url: "cari-pic.php",
            type: "post",
            dataType: "json",
            data: { search: request.term },
            success: function(data) { response(data); },
            error: function() { response([]); }
          });
        },
        appendTo: "#participantModal .modal-content",
        select: function(event, ui) {
          // ui.item.label => "Nama (email)", ui.item.value => email, ui.item.name => Nama
          const pickedName = ui.item.name || (ui.item.label ? String(ui.item.label).split(" (")[0] : "");
          $("#participantName").val(pickedName);
          $("#participantEmail").val(ui.item.value);
          return false;
        },
        focus: function(event, ui) {
          const focusedName = ui.item.name || (ui.item.label ? String(ui.item.label).split(" (")[0] : "");
          $("#participantName").val(focusedName);
          return false;
        },
        minLength: 2
      });
    }
  }
} catch (e) {
  console.warn("Autocomplete init failed:", e);
}
// Color select defaulting handled in openModalForAdd/handleEventSelection
