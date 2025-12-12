#!/bin/bash

# Start Laravel development server and queue worker together
echo "Starting Laravel server and queue worker..."
echo "Server: http://localhost:8000"
echo "Press Ctrl+C to stop both processes"
echo ""

# Run both commands concurrently
php artisan serve &
SERVER_PID=$!

php artisan queue:work &
QUEUE_PID=$!

# Wait for user interrupt
trap "kill $SERVER_PID $QUEUE_PID; exit" INT TERM

# Wait for both processes
wait

