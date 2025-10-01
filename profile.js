$(function () {
  const token = localStorage.getItem('sessionToken');
  if (!token) {
    window.location.href = 'login.html';
    return;
  }

  const userLocal = JSON.parse(localStorage.getItem('user') || '{}');
  if (userLocal.name) {
    $('#welcome').text(`Welcome, ${userLocal.name}`);
  }

  function loadProfile() {
    $.ajax({
      url: 'php/profile.php?action=get',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ token })
    }).done(function (res) {
      if (!res.success) {
        $('#profileAlert').removeClass('d-none alert-success').addClass('alert-danger').text(res.message || 'Failed to load profile');
        if (res.code === 'AUTH') setTimeout(() => { localStorage.clear(); window.location.href = 'login.html'; }, 800);
        return;
      }
      const u = res.data.user;
      $('#name').val(u.name);
      $('#email').val(u.email);
      $('#age').val(u.age || '');
      $('#dob').val(u.dob || '');
      $('#contact').val(u.contact || '');
      localStorage.setItem('user', JSON.stringify(u));
    }).fail(function () {
      $('#profileAlert').removeClass('d-none alert-success').addClass('alert-danger').text('Network error');
    });
  }

  loadProfile();

  $('#profileForm').on('submit', function (e) {
    e.preventDefault();
    const payload = {
      token,
      age: $('#age').val() ? parseInt($('#age').val(), 10) : null,
      dob: $('#dob').val() || null,
      contact: $('#contact').val().trim() || null
    };

    $.ajax({
      url: 'php/profile.php?action=update',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload)
    }).done(function (res) {
      if (res.success) {
        $('#saveStatus').text('Saved');
        setTimeout(() => $('#saveStatus').text(''), 1500);
        loadProfile();
      } else {
        $('#profileAlert').removeClass('d-none alert-success').addClass('alert-danger').text(res.message || 'Update failed');
      }
    }).fail(function () {
      $('#profileAlert').removeClass('d-none alert-success').addClass('alert-danger').text('Network error');
    });
  });

  $('#logoutBtn').on('click', function () {
    $.ajax({
      url: 'php/profile.php?action=logout',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ token })
    }).always(function () {
      localStorage.clear();
      window.location.href = 'login.html';
    });
  });
});
