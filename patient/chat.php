<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["patient_id"])) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "../includes/functions.php";

// Get doctor ID from URL parameter
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

// Check if doctor exists and is approved
$doctor = null;
if ($doctor_id > 0) {
    $sql = "SELECT id, name, speciality, profile_picture FROM doctors WHERE id = ? AND status = 'approved'";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $doctor = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

// Get all doctors the patient has chatted with
$chat_doctors = [];
$chats_sql = "SELECT d.id, d.name, d.speciality, d.profile_picture, 
             (SELECT COUNT(*) FROM chat_messages WHERE sender_id = d.id AND receiver_id = ? AND sender_type = 'doctor' AND is_read = 0) as unread_count,
             (SELECT created_at FROM chat_messages 
              WHERE (sender_id = d.id AND receiver_id = ? AND sender_type = 'doctor') 
              OR (sender_id = ? AND receiver_id = d.id AND sender_type = 'patient') 
              ORDER BY created_at DESC LIMIT 1) as last_message_time
             FROM doctors d
             WHERE d.status = 'approved' AND d.id IN (
                SELECT DISTINCT 
                    CASE 
                        WHEN sender_id = ? AND sender_type = 'patient' THEN receiver_id
                        WHEN receiver_id = ? AND sender_type = 'doctor' THEN sender_id
                    END
                FROM chat_messages
                WHERE (sender_id = ? AND sender_type = 'patient') OR (receiver_id = ? AND sender_type = 'doctor')
             )
             ORDER BY last_message_time DESC";

if ($stmt = mysqli_prepare($conn, $chats_sql)) {
    $patient_id = $_SESSION["patient_id"];
    mysqli_stmt_bind_param($stmt, "iiiiiii", $patient_id, $patient_id, $patient_id, $patient_id, $patient_id, $patient_id, $patient_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $chat_doctors[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Doctor - PawPoint</title>
    <link rel="stylesheet" href="../css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .chat-container {
            display: flex;
            height: calc(100vh - 200px);
            min-height: 500px;
            margin-top: 20px;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        .chat-sidebar {
            width: 300px;
            background-color: var(--light-gray);
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--white);
        }
        
        .chat-header {
            padding: 15px;
            background-color: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
        }
        
        .chat-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #f9f9f9;
        }
        
        .chat-input {
            padding: 15px;
            border-top: 1px solid #ddd;
            display: flex;
        }
        
        .chat-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--radius-sm);
            margin-right: 10px;
        }
        
        .chat-input button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: var(--radius-sm);
            cursor: pointer;
        }
        
        .chat-input button:hover {
            background-color: var(--primary-dark);
        }
        
        .chat-user {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        .chat-user:hover, .chat-user.active {
            background-color: #e9e9e9;
        }
        
        .chat-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        
        .chat-user-info {
            flex: 1;
        }
        
        .chat-user-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .chat-user-status {
            font-size: 0.8rem;
            color: #666;
        }
        
        .unread-badge {
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 10px;
            position: relative;
            clear: both;
        }
        
        .message-received {
            background-color: #e9e9e9;
            float: left;
            border-bottom-left-radius: 0;
        }
        
        .message-sent {
            background-color: var(--primary-light);
            color: white;
            float: right;
            border-bottom-right-radius: 0;
        }
        
        .message-time {
            font-size: 0.7rem;
            margin-top: 5px;
            opacity: 0.7;
        }
        
        .message-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.7rem;
            margin-top: 5px;
            opacity: 0.7;
        }
        
        .read-status {
            margin-left: 5px;
        }
        
        .delete-message {
            cursor: pointer;
            margin-left: 5px;
            color: #ff5555;
        }
        
        .delete-message:hover {
            opacity: 1;
        }
        
        #imagePreview {
            position: relative;
        }
        
        #cancelUpload {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            padding: 0;
        }
        
        .no-chat-selected {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
        }
        
        .no-chat-selected i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .empty-chat-list {
            padding: 20px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    
    <div class="container">
        <h2>Chat with Doctors</h2>
        
        <div class="chat-container">
            <div class="chat-sidebar">
                <?php if (empty($chat_doctors)): ?>
                    <div class="empty-chat-list">
                        <i class="fas fa-comments fa-2x"></i>
                        <p>No chat history found. Start a conversation with a doctor from the Find Doctor page.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($chat_doctors as $chat_doctor): ?>
                        <div class="chat-user <?php echo ($doctor && $doctor['id'] == $chat_doctor['id']) ? 'active' : ''; ?>" 
                             onclick="window.location.href='chat.php?doctor_id=<?php echo $chat_doctor['id']; ?>'">
                            <img src="../uploads/profile_pictures/<?php echo !empty($chat_doctor['profile_picture']) ? htmlspecialchars($chat_doctor['profile_picture']) : 'default.jpg'; ?>" 
                                 alt="Dr. <?php echo htmlspecialchars($chat_doctor['name']); ?>">
                            <div class="chat-user-info">
                                <div class="chat-user-name">Dr. <?php echo htmlspecialchars($chat_doctor['name']); ?></div>
                                <div class="chat-user-status"><?php echo htmlspecialchars($chat_doctor['speciality']); ?></div>
                            </div>
                            <?php if ($chat_doctor['unread_count'] > 0): ?>
                                <div class="unread-badge"><?php echo $chat_doctor['unread_count']; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="chat-main">
                <?php if ($doctor): ?>
                    <div class="chat-header">
                        <img src="../uploads/profile_pictures/<?php echo !empty($doctor['profile_picture']) ? htmlspecialchars($doctor['profile_picture']) : 'default.jpg'; ?>" 
                             alt="Dr. <?php echo htmlspecialchars($doctor['name']); ?>">
                        <div>
                            <h3>Dr. <?php echo htmlspecialchars($doctor['name']); ?></h3>
                            <small><?php echo htmlspecialchars($doctor['speciality']); ?></small>
                        </div>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages"></div>
                    
                    <div class="chat-input">
                        <form id="messageForm" enctype="multipart/form-data">
                            <div class="input-group">
                                <input type="text" id="messageInput" class="form-control" placeholder="Type your message...">
                                <label for="imageUpload" class="btn btn-outline-secondary">
                                    <i class="fas fa-image"></i>
                                </label>
                                <input type="file" id="imageUpload" style="display: none;" accept="image/*">
                                <button type="button" id="sendButton" class="btn btn-primary">Send</button>
                            </div>
                            <div id="imagePreview" style="display: none; margin-top: 10px;">
                                <img id="previewImg" style="max-width: 200px; max-height: 200px;">
                                <button type="button" id="cancelUpload" class="btn btn-sm btn-danger">Cancel</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="no-chat-selected">
                        <i class="fas fa-comments"></i>
                        <p>Select a doctor to start chatting</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include "../includes/footer.php"; ?>
    
    <?php if ($doctor): ?>
    <script>
        const doctorId = <?php echo $doctor_id; ?>;
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const imageUpload = document.getElementById('imageUpload');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const cancelUpload = document.getElementById('cancelUpload');
        
        let selectedImage = null;
        
        // Function to load messages
        function loadMessages() {
            fetch(`../includes/get_messages.php?user_id=${doctorId}`)
                .then(response => response.json())
                .then(data => {
                    chatMessages.innerHTML = '';
                    
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(message => {
                            const messageElement = document.createElement('div');
                            messageElement.className = `message ${message.is_mine ? 'message-sent' : 'message-received'}`;
                            messageElement.dataset.messageId = message.id;
                            
                            const messageText = document.createElement('div');
                            
                            // Check if it's an image message
                            if (message.is_image) {
                                const imagePath = message.message.replace('[IMAGE:', '').replace(']', '');
                                const img = document.createElement('img');
                                img.src = `../${imagePath}`;
                                img.style.maxWidth = '200px';
                                img.style.maxHeight = '200px';
                                img.style.borderRadius = '8px';
                                messageText.appendChild(img);
                            } else {
                                messageText.textContent = message.message;
                            }
                            
                            messageElement.appendChild(messageText);
                            
                            const messageFooter = document.createElement('div');
                            messageFooter.className = 'message-footer';
                            
                            const messageTime = document.createElement('span');
                            messageTime.className = 'message-time';
                            const date = new Date(message.created_at);
                            messageTime.textContent = date.toLocaleString();
                            messageFooter.appendChild(messageTime);
                            
                            // Add read status indicator for sent messages
                            if (message.is_mine) {
                                const readStatus = document.createElement('span');
                                readStatus.className = 'read-status';
                                readStatus.innerHTML = message.is_read ? 
                                    '<i class="fas fa-check-double" title="Read"></i>' : 
                                    '<i class="fas fa-check" title="Delivered"></i>';
                                messageFooter.appendChild(readStatus);
                                
                                // Add delete button for own messages
                                const deleteBtn = document.createElement('span');
                                deleteBtn.className = 'delete-message';
                                deleteBtn.innerHTML = '<i class="fas fa-trash-alt" title="Delete"></i>';
                                deleteBtn.onclick = function(e) {
                                    e.stopPropagation();
                                    deleteMessage(message.id);
                                };
                                messageFooter.appendChild(deleteBtn);
                            }
                            
                            messageElement.appendChild(messageFooter);
                            chatMessages.appendChild(messageElement);
                        });
                        
                        // Scroll to bottom
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }
        
        // Function to delete message
        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message?')) {
                const formData = new FormData();
                formData.append('message_id', messageId);
                
                fetch('../includes/delete_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadMessages();
                    } else {
                        alert('Failed to delete message: ' + data.message);
                    }
                })
                .catch(error => console.error('Error deleting message:', error));
            }
        }
        
        // Function to send message
        function sendMessage() {
            const message = messageInput.value.trim();
            
            if (message || selectedImage) {
                if (selectedImage) {
                    // Send image
                    const formData = new FormData();
                    formData.append('receiver_id', doctorId);
                    formData.append('image', selectedImage);
                    
                    fetch('../includes/send_image.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            resetImageUpload();
                            loadMessages();
                        } else {
                            alert('Failed to send image: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error sending image:', error));
                } else if (message) {
                    // Send text message
                    const formData = new FormData();
                    formData.append('receiver_id', doctorId);
                    formData.append('message', message);
                    
                    fetch('../includes/send_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            messageInput.value = '';
                            loadMessages();
                        } else {
                            alert('Failed to send message: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error sending message:', error));
                }
            }
        }
        
        // Function to handle image upload
        function handleImageUpload(e) {
            const file = e.target.files[0];
            if (file) {
                selectedImage = file;
                previewImg.src = URL.createObjectURL(file);
                imagePreview.style.display = 'block';
                messageInput.disabled = true;
            }
        }
        
        // Function to reset image upload
        function resetImageUpload() {
            selectedImage = null;
            imageUpload.value = '';
            imagePreview.style.display = 'none';
            messageInput.disabled = false;
        }
        
        // Event listeners
        sendButton.addEventListener('click', sendMessage);
        
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        imageUpload.addEventListener('change', handleImageUpload);
        
        cancelUpload.addEventListener('click', resetImageUpload);
        
        // Load messages initially
        loadMessages();
        
        // Refresh messages every 5 seconds
        setInterval(loadMessages, 5000);
    </script>
    <?php endif; ?>
</body>
</html>