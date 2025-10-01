$(function () {
  const show = (el, type, msg) => {
    const $a = $(el);
    $a.removeClass('d-none alert-success alert-danger');
    $a.addClass(`alert-${type}`);
    $a.text(msg);
  };

  $('#registerForm').on('submit', function (e) {
    e.preventDefault();

    const payload = {
      name: $('#name').val().trim(),
      email: $('#email').val().trim(),
      password: $('#password').val(),
      age: $('#age').val() ? parseInt($('#age').val(), 10) : null,
      dob: $('#dob').val() || null,
      contact: $('#contact').val().trim() || null
    };

    $.ajax({
      url: 'php/register.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload)
    }).done(function (res) {
      if (res.success) {
        show('#registerAlert', 'success', 'Registration successful. Redirecting to login...');
        setTimeout(() => window.location.href = 'login.html', 900);
      } else {
        show('#registerAlert', 'danger', res.message || 'Registration failed');
      }
    }).fail(function () {
      show('#registerAlert', 'danger', 'Network error');
    });
  });
});
