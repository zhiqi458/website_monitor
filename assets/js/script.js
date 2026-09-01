document.addEventListener("DOMContentLoaded", function() {
    // 鼠标悬停在历史天数色块上时自动高亮提示
    const dayBars = document.querySelectorAll('.day-bar');
    dayBars.forEach(bar => {
        bar.addEventListener('mouseenter', function() {
            this.style.opacity = '0.7';
        });
        bar.addEventListener('mouseleave', function() {
            this.style.opacity = '1';
        });
    });
});