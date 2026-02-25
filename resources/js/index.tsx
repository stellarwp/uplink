import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App } from '@/App';
import '@css/globals.css';

const queryClient = new QueryClient( {
    defaultOptions: {
        queries: {
            staleTime: 5 * 60 * 1000, // 5 minutes
            retry: 1,
        },
    },
} );

function Root() {
    return (
        <QueryClientProvider client={ queryClient }>
            <App />
        </QueryClientProvider>
    );
}

const rootElement = document.getElementById( 'uplink-root' );

if ( rootElement ) {
    createRoot( rootElement ).render( <Root /> );
}
