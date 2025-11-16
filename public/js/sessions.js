/**
 * Sessions Management JavaScript
 * Handles fetching and revoking active sessions
 */

(function() {
    'use strict';

    const sessionsContainer = document.getElementById('sessionsContainer');
    const CSRF_TOKEN = window.CSRF_TOKEN || '';

    /**
     * Fetch active sessions from API
     */
    async function fetchSessions() {
        try {
            const response = await fetch('/fit-brawl/public/php/api/get_sessions.php', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                renderSessions(data.sessions);
            } else {
                showError('Failed to load sessions: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error fetching sessions:', error);
            showError('Failed to load sessions. Please refresh the page.');
        }
    }

    /**
     * Render sessions list
     */
    function renderSessions(sessions) {
        if (!sessions || sessions.length === 0) {
            sessionsContainer.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 12px; display: block;"></i>
                    <p>No active sessions found</p>
                </div>
            `;
            return;
        }

        const currentSession = sessions.find(s => s.is_current);
        const otherSessions = sessions.filter(s => !s.is_current);

        let html = '';

        // Current session
        if (currentSession) {
            html += `
                <div class="session-card" style="background: #f0f9ff; border: 2px solid #0ea5e9; margin-bottom: 20px; padding: 20px; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <strong style="font-size: 16px;">${escapeHtml(currentSession.device)}</strong>
                                <span style="background: #0ea5e9; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">Current Session</span>
                            </div>
                            <p style="color: #666; font-size: 13px; margin: 4px 0;">
                                <i class="fas fa-map-marker-alt"></i> ${escapeHtml(currentSession.ip_address)}
                            </p>
                            <p style="color: #666; font-size: 13px; margin: 4px 0;">
                                <i class="fas fa-clock"></i> Last active: ${formatTimeAgo(currentSession.minutes_inactive)}
                            </p>
                            <p style="color: #666; font-size: 13px; margin: 4px 0;">
                                <i class="fas fa-calendar"></i> Logged in: ${formatDate(currentSession.login_time)}
                            </p>
                        </div>
                    </div>
                </div>
            `;
        }

        // Other sessions
        if (otherSessions.length > 0) {
            html += `
                <div style="margin-top: 30px;">
                    <h3 style="font-size: 18px; margin-bottom: 16px;">Other Active Sessions</h3>
            `;

            otherSessions.forEach(session => {
                html += `
                    <div class="session-card" style="background: white; border: 1px solid #ddd; margin-bottom: 12px; padding: 16px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <strong style="font-size: 15px;">${escapeHtml(session.device)}</strong>
                                </div>
                                <p style="color: #666; font-size: 13px; margin: 4px 0;">
                                    <i class="fas fa-map-marker-alt"></i> ${escapeHtml(session.ip_address)}
                                </p>
                                <p style="color: #666; font-size: 13px; margin: 4px 0;">
                                    <i class="fas fa-clock"></i> Last active: ${formatTimeAgo(session.minutes_inactive)}
                                </p>
                                <p style="color: #666; font-size: 13px; margin: 4px 0;">
                                    <i class="fas fa-calendar"></i> Logged in: ${formatDate(session.login_time)}
                                </p>
                            </div>
                            <button class="btn-revoke" onclick="revokeSession('${session.full_session_id}')"
                                    style="background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                <i class="fas fa-times"></i> Revoke
                            </button>
                        </div>
                    </div>
                `;
            });

            html += `
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                        <button class="btn-revoke-all" onclick="revokeAllSessions()"
                                style="background: #dc2626; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">
                            <i class="fas fa-sign-out-alt"></i> Revoke All Other Sessions
                        </button>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div style="margin-top: 30px; text-align: center; padding: 20px; color: #999;">
                    <p>No other active sessions</p>
                </div>
            `;
        }

        sessionsContainer.innerHTML = html;
    }

    /**
     * Revoke a specific session
     */
    async function revokeSession(sessionId) {
        if (!confirm('Are you sure you want to revoke this session? The user will be logged out from that device.')) {
            return;
        }

        try {
            const response = await fetch('/fit-brawl/public/php/api/revoke_session.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRF_TOKEN
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    csrf_token: CSRF_TOKEN
                })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess('Session revoked successfully');
                fetchSessions(); // Refresh list
            } else {
                showError('Failed to revoke session: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error revoking session:', error);
            showError('Failed to revoke session. Please try again.');
        }
    }

    /**
     * Revoke all other sessions
     */
    async function revokeAllSessions() {
        if (!confirm('Are you sure you want to revoke all other sessions? You will remain logged in on this device only.')) {
            return;
        }

        try {
            const response = await fetch('/fit-brawl/public/php/api/revoke_session.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRF_TOKEN
                },
                body: JSON.stringify({
                    revoke_all: true,
                    csrf_token: CSRF_TOKEN
                })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess('All other sessions revoked successfully');
                fetchSessions(); // Refresh list
            } else {
                showError('Failed to revoke sessions: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error revoking sessions:', error);
            showError('Failed to revoke sessions. Please try again.');
        }
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 20px; border-radius: 6px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    /**
     * Show error message
     */
    function showError(message) {
        const toast = document.createElement('div');
        toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #dc2626; color: white; padding: 12px 20px; border-radius: 6px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    /**
     * Format time ago
     */
    function formatTimeAgo(minutes) {
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
        const days = Math.floor(hours / 24);
        return `${days} day${days !== 1 ? 's' : ''} ago`;
    }

    /**
     * Format date
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Make functions globally available
    window.revokeSession = revokeSession;
    window.revokeAllSessions = revokeAllSessions;

    // Load sessions on page load
    document.addEventListener('DOMContentLoaded', fetchSessions);
})();

