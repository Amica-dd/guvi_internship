$(function () {
  const show = (el, type, msg) => {
    const $a = $(el);
    $a.removeClass('d-none alert-success alert-danger');
    $a.addClass(`alert-${type}`);
    $a.text(msg);
  };

  $('#loginForm').on('submit', function (e) {
    e.preventDefault();

    const payload = {
      email: $('#email').val().trim(),
      password: $('#password').val()
    };

    $.ajax({
      url: 'php/login.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload)
    }).done(function (res) {
      if (res.success) {
        // store in localStorage only
        localStorage.setItem('sessionToken', res.data.token);
        localStorage.setItem('user', JSON.stringify(res.data.user));
        window.location.href = 'profile.html';
      } else {
        show('#loginAlert', 'danger', res.message || 'Invalid credentials');
      }
    }).fail(function () {
      show('#loginAlert', 'danger', 'Network error');
    });
  });
});
