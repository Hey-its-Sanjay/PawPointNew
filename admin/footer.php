            <!-- Main content ends here -->
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // User menu toggle functionality
            $('.user-menu-toggle').click(function(e) {
                e.preventDefault();
                // Toggle menu code can be added here if needed
            });
            
            // Current time and date
            function updateTime() {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                };
                const formattedDate = now.toLocaleDateString('en-US', options);
                $('#current-time').text(formattedDate);
            }
            
            // Update time every second
            updateTime();
            setInterval(updateTime, 1000);
        });
    </script>
</body>
</html> 