<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>PawPoint Veterinary Care</h3>
                <p>Your trusted partner in pet healthcare</p>
            </div>
            <div class="footer-section">
                <h4>Contact Us</h4>
                <p>
                    <i class="fas fa-phone"></i> +977-1-4485834<br>
                    <i class="fas fa-envelope"></i> info@pawpoint.com<br>
                    <i class="fas fa-map-marker-alt"></i> Kathmandu, Nepal
                </p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="find_doctor.php">Find a Doctor</a></li>
                    <li><a href="products.php">Pet Products</a></li>
                    <li><a href="appointments.php">My Appointments</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> PawPoint Veterinary Care. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
.footer {
    background-color: #4a7c59;
    color: #fff;
    padding: 40px 0 20px;
    margin-top: 50px;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 30px;
    margin-bottom: 30px;
}

.footer-section {
    flex: 1;
    min-width: 250px;
}

.footer-section h3 {
    color: #fff;
    margin-bottom: 15px;
    font-size: 1.5em;
}

.footer-section h4 {
    color: #fff;
    margin-bottom: 15px;
    font-size: 1.2em;
}

.footer-section p {
    line-height: 1.6;
    margin: 0;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 8px;
}

.footer-section ul li a {
    color: #fff;
    text-decoration: none;
    transition: opacity 0.3s;
}

.footer-section ul li a:hover {
    opacity: 0.8;
}

.footer-section i {
    margin-right: 8px;
    width: 20px;
}

.footer-bottom {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-bottom p {
    margin: 0;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .footer-section {
        flex: 100%;
        text-align: center;
    }
    
    .footer-content {
        flex-direction: column;
        gap: 20px;
    }
}
</style>
