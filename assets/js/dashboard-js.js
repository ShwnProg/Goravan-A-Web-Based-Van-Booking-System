/**
 * dashboard-js.js
 * Bar chart + activity feed for the admin dashboard.
 * PHP injects: dailyLabels, dailyData
 * Requires: Chart.js 4+ loaded before this script.
 */

(function () {
    'use strict';

    // ── Daily bookings bar chart ──────────────────────────────────────────────
    function initDailyChart() {
        const canvas = document.getElementById('chartDaily');
        if (!canvas) return;

        // Build a full 7-day window aligned to today, fill gaps with 0
        const lookup = {};
        dailyLabels.forEach((d, i) => { lookup[d] = dailyData[i]; });

        const labels = [];
        const data   = [];
        const colors = [];
        const today  = new Date();

        for (let i = 6; i >= 0; i--) {
            const d   = new Date(today);
            d.setDate(d.getDate() - i);
            const key = d.toISOString().slice(0, 10);
            labels.push(d.toLocaleDateString('en-PH', { weekday: 'short' }));
            data.push(lookup[key] ?? 0);
            // Highlight today (i === 0) with accent orange, rest grey
            colors.push(i === 0 ? '#F97316' : '#e5e7eb');
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor    : colors,
                    hoverBackgroundColor: '#F97316',
                    borderRadius       : 5,
                    borderSkipped      : false,
                    borderWidth        : 0,
                }],
            },
            options: {
                responsive         : true,
                maintainAspectRatio: false,
                animation          : { duration: 500, easing: 'easeOutQuart' },
                plugins: {
                    legend : { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor     : '#f1f5f9',
                        bodyColor      : '#94a3b8',
                        padding        : 10,
                        cornerRadius   : 6,
                        displayColors  : false,
                        callbacks: {
                            label: ctx =>
                                `${ctx.parsed.y} booking${ctx.parsed.y !== 1 ? 's' : ''}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid  : { display: false },
                        border: { display: false },
                        ticks : {
                            color       : '#c0c0c0',
                            font        : { size: 10 },
                            maxRotation : 0,
                        },
                    },
                    y: {
                        display: false,
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    // ── Activity feed ─────────────────────────────────────────────────────────
    // Replace this static list with a real AJAX call to GetRecentActivity()
    // when that endpoint is ready. Structure expected:
    // [{ text: 'HTML string', time: 'x min ago', color: '#hex' }, ...]
    function initActivityFeed() {
        const container = document.getElementById('db-activity');
        if (!container) return;

        // TODO: replace with fetch('/api/recent-activity') when endpoint exists
        const activities = [
            { text: 'New booking arrived',        time: 'Just now',   color: '#F97316' },
            { text: 'A booking was cancelled',     time: '18 min ago', color: '#9ca3af' },
            { text: 'Schedule marked complete',    time: '1 hr ago',   color: '#16a34a' },
            { text: 'New passenger registered',    time: '2 hr ago',   color: '#378ADD' },
            { text: 'Booking approved',            time: '3 hr ago',   color: '#16a34a' },
        ];

        activities.forEach(a => {
            const item = document.createElement('div');
            item.className = 'db-act';
            item.innerHTML = `
                <div class="db-act__dot" style="background:${a.color}"></div>
                <div class="db-act__body">
                    <div class="db-act__text">${a.text}</div>
                    <div class="db-act__time">${a.time}</div>
                </div>`;
            container.appendChild(item);
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    function init() {
        if (typeof Chart === 'undefined') {
            console.warn('dashboard-js.js: Chart.js not loaded.');
            return;
        }
        initDailyChart();
        initActivityFeed();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();