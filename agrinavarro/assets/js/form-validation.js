(function () {
  var messageId = 0;

  function getWrapper(field) {
    return field.closest('.mb-3, .form-group, .form-field') || field.parentElement;
  }

  function clearInvalidState(field) {
    field.classList.remove('validation-invalid');
    field.removeAttribute('aria-invalid');

    var labels = field.labels || [];
    Array.prototype.forEach.call(labels, function (label) {
      label.classList.remove('validation-label');
    });

    var wrapper = getWrapper(field);
    if (wrapper) {
      wrapper.classList.remove('validation-shake');
      if (!wrapper.querySelector('.validation-invalid')) {
        wrapper.classList.remove('has-validation-error');
      }
    }

    if (field._growthtopiaValidationMessage) {
      var describedBy = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(function (id) {
        return id && id !== field._growthtopiaValidationMessage.id;
      });
      if (describedBy.length) {
        field.setAttribute('aria-describedby', describedBy.join(' '));
      } else {
        field.removeAttribute('aria-describedby');
      }
      field._growthtopiaValidationMessage.remove();
      field._growthtopiaValidationMessage = null;
    }
  }

  function showInvalidState(field) {
    var wrapper = getWrapper(field);
    field.classList.add('validation-invalid');
    field.setAttribute('aria-invalid', 'true');

    var labels = field.labels || [];
    Array.prototype.forEach.call(labels, function (label) {
      label.classList.add('validation-label');
    });

    if (wrapper) {
      wrapper.classList.remove('validation-shake');
      wrapper.classList.add('has-validation-error');
      void wrapper.offsetWidth;
      wrapper.classList.add('validation-shake');
    }

    if (!field._growthtopiaValidationMessage && wrapper) {
      var message = document.createElement('small');
      message.className = 'validation-message';
      message.id = 'growthtopia-validation-' + (++messageId);
      message.setAttribute('role', 'alert');
      message.textContent = field.validationMessage;
      wrapper.appendChild(message);
      field._growthtopiaValidationMessage = message;

      var describedBy = field.getAttribute('aria-describedby');
      field.setAttribute('aria-describedby', describedBy ? describedBy + ' ' + message.id : message.id);
    } else if (field._growthtopiaValidationMessage) {
      field._growthtopiaValidationMessage.textContent = field.validationMessage;
    }
  }

  function isValidatedField(target) {
    return target instanceof HTMLInputElement
      || target instanceof HTMLSelectElement
      || target instanceof HTMLTextAreaElement;
  }

  document.addEventListener('invalid', function (event) {
    if (isValidatedField(event.target)) showInvalidState(event.target);
  }, true);

  document.addEventListener('input', function (event) {
    if (isValidatedField(event.target) && event.target.checkValidity()) clearInvalidState(event.target);
  });

  document.addEventListener('change', function (event) {
    if (isValidatedField(event.target) && event.target.checkValidity()) clearInvalidState(event.target);
  });
}());
