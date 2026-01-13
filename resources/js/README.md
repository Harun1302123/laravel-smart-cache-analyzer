# Laravel Smart Cache Analyzer - Frontend Components

Real-time dashboard components for Vue and React.

## Installation

```bash
npm install @harun1302123/laravel-smart-cache-dashboard
```

## Vue 3 Usage

```vue
<template>
  <SmartCacheDashboard 
    api-url="/smart-cache"
    websocket-url="ws://localhost:6001"
    :refresh-interval="5000"
  />
</template>

<script setup>
import { SmartCacheDashboard } from '@harun1302123/laravel-smart-cache-dashboard/vue'
</script>
```

## React Usage

```jsx
import React from 'react';
import SmartCacheDashboard from '@harun1302123/laravel-smart-cache-dashboard/react';

function App() {
  return (
    <SmartCacheDashboard 
      apiUrl="/smart-cache"
      websocketUrl="ws://localhost:6001"
      refreshInterval={5000}
    />
  );
}
```

## Props

- `apiUrl` (required): Base URL for the Smart Cache API
- `websocketUrl` (optional): WebSocket URL for real-time updates
- `refreshInterval` (optional): Polling interval in ms (default: 5000)

## Features

- ✅ Real-time stats updates
- ✅ WebSocket support with Laravel Echo
- ✅ Automatic fallback to polling
- ✅ Top queries visualization
- ✅ Cache recommendations display
- ✅ Connection status indicator
- ✅ Responsive design

## Laravel Echo Setup

Install Laravel Echo and Pusher:

```bash
npm install --save laravel-echo pusher-js
```

Configure in your app:

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});
```

## License

MIT
