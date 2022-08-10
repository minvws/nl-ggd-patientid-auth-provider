import { onDomReady } from "@minvws/manon/utils.js";

onDomReady(function initBirthdate() {
  var fieldset = document.querySelector(".birthdate");
  if (!(fieldset instanceof HTMLElement)) {
    return;
  }

  var inputs = Array.from(fieldset.querySelectorAll("input"));

  fieldset.addEventListener("beforeinput", function (event) {
    var target = event.target;
    var index = inputs.indexOf(target);
    if (
      index === 0 ||
      event.inputType !== "deleteContentBackward" ||
      target.value !== ""
    ) {
      return;
    }
    target = inputs[index - 1];
    target.value = target.value.slice(0, -1);
    target.focus();
  });

  fieldset.addEventListener("input", function (event) {
    var target = event.target;
    var index = inputs.indexOf(target);
    if (
      index === inputs.length - 1 ||
      !target.maxLength ||
      target.value.length < target.maxLength
    ) {
      return;
    }
    target = inputs[index + 1];
    target.focus();
    target.setSelectionRange(0, target.value.length);
  });
});
