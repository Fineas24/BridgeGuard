Chart.defaults.color = '#8b949e';
Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
Chart.defaults.font.family = "'JetBrains Mono', monospace";

function getChartGradient(ctx, height) {
    const gradient = ctx.createLinearGradient(0, 0, 0, height);
    gradient.addColorStop(0, 'rgba(0, 200, 150, 0.2)');
    gradient.addColorStop(1, 'rgba(0, 200, 150, 0)');
    return gradient;
}
