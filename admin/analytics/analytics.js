// Analytics — admin
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.bar').forEach(bar => {
    const target = bar.style.height;
    bar.style.height = '0';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        bar.style.transition = 'height 0.7s cubic-bezier(.4,0,.2,1)';
        bar.style.height = target;
      });
    });
  });
});
