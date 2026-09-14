import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

let echoInstance = null;

export function useWebSocket() {
  if (!echoInstance) {
    const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';
    
    echoInstance = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
      wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: `${apiBase}/broadcasting/auth`,
      auth: {
        headers: {
          get Authorization() {
            const token = localStorage.getItem('auth_token');
            return token ? `Bearer ${token}` : '';
          },
          Accept: 'application/json',
        },
      },
    });
  }

  const listenToPlot = (plotId, callback) => {
    return echoInstance.private(`plot.${plotId}`)
      .listen('AnalysisCompleted', callback)
      .listen('.AnalysisCompleted', callback);
  };

  const leavePlot = (plotId) => {
    echoInstance.leave(`plot.${plotId}`);
  };

  return {
    echo: echoInstance,
    listenToPlot,
    leavePlot,
  };
}
