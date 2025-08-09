@extends('layouts.app')

@section('contents')
<meta name="user-id" content="{{ Auth::id() }}">


<style type="text/css">
    .chat-logo{
        width: 45px;
            height: 45px;
    border-radius: 50%;
    }
    .list-group-item.list-group-item-action.active{
 background: var(--primary-bg);
 color:white;
    }
.secondary{
    background: var(--secondary);
}
    .card-header{
        background: var(--primary-bg);
    }
    #membersList li{
        margin:10px;
    }
    .left-align{
        justify-content: flex-end;
    }
    .chat-item{
        max-width: 80%;
    }
    .user-icon{
        width:25px;
    }
      .chat-item img, .chat-item video{
    width: 200px;
    }
    #messagesList::-webkit-scrollbar, #conversationList::-webkit-scrollbar {
    width: 6px; /* Set scrollbar width */
}

#messagesList::-webkit-scrollbar-track {
    background: #f1f1f1; /* Scrollbar track background */
    border-radius: 10px; /* Rounded corners for the track */
}

#messagesList::-webkit-scrollbar-thumb {
    background: #888; /* Scrollbar thumb color */
    border-radius: 10px; /* Rounded corners for the thumb */
}

#messagesList::-webkit-scrollbar-thumb:hover {
    background: #555; /* Change color on hover */
}

/* Firefox Scrollbar Styles */
#messagesList {
    scrollbar-width: thin; /* Thin scrollbar */
    scrollbar-color: #888 #f1f1f1; /* Thumb and track colors */
}

/* General Scrollbar Enhancements */
#messagesList {
    padding-right: 5px; /* Prevent content overlap with scrollbar */
}
.bi.bi-chat{
    color:white;
}
.bi.bi-pin-fill{
    color:red;
}
.list-group-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.pin-icon {
    margin-left: auto;
    cursor: pointer;
}
.pin-icon .bi{
    font-size:17px;
}
.toast-error{
    background-color:red!important;
}
</style>

    <div id="scheduling" class="page-wrapper security_board">

        <div class="content">
        
     <div class="row mt-4" style=""> <!-- Adjust the height to account for the navbar or any fixed headers -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Chats</h2>

                </div>

            </div>
    <!-- Sidebar for Conversations List -->
    <div class="col-md-4 d-flex flex-column" id="conversationSidebar" style="">
        <div class="card flex-grow-1">
            <div class="card-header text-white">
                <h5>{{ __('messages.all_conversation') }}</h5>
            </div>
            <div class="list-group list-group-flush" id="conversationList" style="max-height:70vh; overflow-y:scroll">
                <!-- Conversations will be dynamically added here -->
            </div>
        </div>
    </div>

    <!-- Messages Section -->
    <div class="col-md-8 d-flex flex-column" id="messagesSection" style="height: auto; display: none;">
        <div id="messagesContainer" class="flex-grow-1" >
            <div class="card h-100" style="min-height: 70vh;">
                <div class="card-header  text-white d-flex justify-content-between align-items-center">
                    <button class="btn btn-sm btn-light" id="backButton" style="display: none;">Back to Conversations</button>
                    <span>{{ __('messages.message') }}</span>
                    <button class="btn btn-sm btn-light" id="viewMembersBtn">{{ __('messages.members_list') }}</button>
                      <button class="btn btn-primary btn-sm text-white" data-bs-toggle="modal" data-bs-target="#oneToOneModal">
         {{ __('messages.start_chat') }}</button>
          <button class="btn btn-primary btn-sm text-white" data-bs-toggle="modal" data-bs-target="#groupChatModal">
         {{ __('New Group') }}</button>
                </div>
                <div id="messagesList" style="max-height:70vh; overflow-y:scroll" class="card-body">
                    <div  style="max-height:75vh; overflow-y: auto; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    <!-- Default Icon for Selecting Conversation -->
    <div id="defaultMessage" class="text-center" style="display: flex; flex-direction: column; align-items: center;">
        <i class="bi bi-chat-dots" style="font-size: 50px;"></i> <!-- Default Chat Icon -->
        <p>{{ __('messages.select_chat_prompt') }}</p>
    </div>
    </div>
    
    <!-- Messages will be dynamically added here -->
</div>
                <div class="card-footer">
                    <div class="input-group">
                        <input type="text" class="form-control" id="messageInput" placeholder="{{ __('messages.write_message_here') }}">
                        <button class="btn btn-light" id="emojiBtn">😊</button>
                        <div id="emojiPickerContainer" style="position: absolute; display: block; z-index: 1000;"></div>

                        <input type="file" id="mediaInput" class="d-none" accept="image/*,video/*">
                        <button class="btn btn-secondary" onclick="document.getElementById('mediaInput').click()">📎</button>
                        <button class="btn btn-primary" id="sendMessageBtn">{{ __('messages.send') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


    </div>
</div>
<!-- Group Members Modal -->
<div class="modal fade" id="membersModal" tabindex="-1" aria-labelledby="membersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="membersModalLabel">Group Members</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="membersList">
                <!-- Group members will be dynamically added here -->
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="oneToOneModal" tabindex="-1" aria-labelledby="oneToOneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="oneToOneModalLabel">Start One-to-One Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('conversations') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select user_select" name="users_id[]" id="user_id" required>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 text-end">
                        <button type="submit" class="btn btn-primary">Start Chat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="groupChatModal" tabindex="-1" aria-labelledby="groupChatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupChatModalLabel">Create Group Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('conversations') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="group_name" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="group_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="group_users" class="form-label">Select Users</label>
                        <select class="form-select group-user" name="users_id[]" id="group_users" multiple required>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple users.</small>
                    </div>

                    <div class="mb-3">
                        <label for="group_icon" class="form-label">Group Icon</label>
                        <input type="file" class="form-control" accept=".jpg, .png" id="group_icon" name="icon">
                    </div>

                    <div class="mb-3 text-end">
                        <button type="submit" class="btn btn-success">Create Group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/pusher-js@7.0.3/dist/web/pusher.min.js"></script>
<!-- EmojiMart Styles -->

<!-- EmojiMart Script -->
<script src="https://cdn.jsdelivr.net/npm/emoji-mart@5.6.0/dist/browser.min.js"></script>
 <script>
            $(document).ready(function() {
                 $(".user_select").select2({
            dropdownParent: $("#oneToOneModal")
        });
                    $(".group-user").select2({
            dropdownParent: $("#groupChatModal")
        });
                $('#profile_picture').on('change', function() {
                    const fileInput = this;
                    if (fileInput.files && fileInput.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('label[for="profile_picture"] img').attr('src', e.target.result);
                        };
                        reader.readAsDataURL(fileInput.files[0]);
                    }
                });
            });
        </script>
<script>
    Pusher.logToConsole = true;
    var pusher = new Pusher('f45c24b39e2a5f3b2239', { cluster: 'mt1' });

    // Emoji Picker
     const pickerContainer = document.getElementById('emojiPickerContainer');
    const emojiButton = document.getElementById('emojiBtn');
    const messageInput = document.getElementById('messageInput');

    // Create Emoji Picker
    const picker = new EmojiMart.Picker({
        onEmojiSelect: emoji => {
            messageInput.value += emoji.native;  // Add emoji to input
        },
        theme: 'light',
    });

    pickerContainer.appendChild(picker);

    // Toggle picker visibility
    emojiButton.addEventListener('click', () => {
        pickerContainer.style.display = pickerContainer.style.display === 'none' ? 'block' : 'none';
        const rect = emojiButton.getBoundingClientRect();
        pickerContainer.style.bottom = 0 + 'px';
    });

    // Hide picker on outside click
    document.addEventListener('click', (e) => {
        if (!pickerContainer.contains(e.target) && e.target !== emojiButton) {
            pickerContainer.style.display = 'none';
        }
    });


function loadConversations() {
    fetch('load/conversations')
        .then(response => response.json())
        .then(conversations => {
            const conversationList = document.getElementById('conversationList');
            conversationList.innerHTML = ''; // Clear existing list

            // Separate and sort conversations by pinned status
            const sortedConversations = [
                ...conversations.filter(c => c.pinned),
                ...conversations.filter(c => !c.pinned)
            ];

          sortedConversations.forEach(conversation => {
    const conversationItem = document.createElement('a');
    conversationItem.href = '#';
    conversationItem.className = 'list-group-item list-group-item-action d-flex align-items-center';
    conversationItem.id = `chat-${conversation.id}`;
    conversationItem.onclick = () => loadMessages(conversation.id);

    // Content wrapper
    const contentWrapper = document.createElement('div');
    contentWrapper.className = 'd-flex align-items-center';

    // Generate content based on conversation type
    if (conversation.type === 'group') {
        const logoUrl = conversation.icon_path 
            ? `${baseUrl +'/'+ conversation.icon_path}` 
            : 'defaultLogoPath';
        const departmentName = conversation.name || 'Group';

        contentWrapper.innerHTML = `
            <img class="chat-logo" src="${logoUrl}" alt="${departmentName} Logo">
            <span>${departmentName}</span>
        `;
    } else {
        conversation.participants.forEach(user => {
            const loggedInUserId = {{ Auth::id() }};
            if (user.id !== loggedInUserId) {
                const profilePictureUrl = user.profile_pic 
                    ? `${baseUrl + user.profile_pic}` 
                    : "https://cdn-icons-png.flaticon.com/512/1053/1053244.png";
                const userName = user.name || 'Unknown User';

                contentWrapper.innerHTML = `
                    <img class="chat-logo" src="${profilePictureUrl}" alt="${userName} Picture">
                    <span>${userName}</span>
                `;
            }
        });
    }

    // Add pin button
    const pinButton = document.createElement('label');
    pinButton.className = 'pin-icon';
    const icon = document.createElement('i');
    icon.className = conversation.pinned ? 'bi bi-pin-fill' : 'bi bi-pin';
    pinButton.appendChild(icon);

    pinButton.onclick = event => {
        event.stopPropagation();
        togglePin(conversation.id, !conversation.pinned);
        icon.className = !conversation.pinned ? 'bi bi-pin-fill' : 'bi bi-pin';
    };

    // Append content and pin button
    conversationItem.appendChild(contentWrapper);
    conversationItem.appendChild(pinButton);
    conversationList.appendChild(conversationItem);
});

        })
        .catch(error => console.error('Error loading conversations:', error));
}



   function loadMessages(conversationId) {
    fetch(`/conversations/${conversationId}/messages`)
        .then(response => response.json())
        .then(messages => {
            const messagesList = document.getElementById('messagesList');
            messagesList.innerHTML = '';

            messages.forEach(message => {
                const profilePictureUrl = message.sender.profile_pic 
                    ? `${baseUrl + message.sender.profile_pic}` 
                    : "https://cdn-icons-png.flaticon.com/512/1053/1053244.png"; // Fallback icon
                
                const isCurrentUser = message.sender.id === {{ Auth::id() }};
                const messageDiv = document.createElement('div');
                messageDiv.className = `align-items-start mb-2 d-flex ${isCurrentUser ? 'left-align' : ''}`;

                // User icon and name
                const iconHTML = `<img src="${profilePictureUrl}" class="user-icon me-2">`;

                // Message content with attachment handling
                let messageContent = `<div class="p-2 chat-item rounded" style="background-color: ${isCurrentUser ? '#d1ecf1' : '#f8d7da'};">
                    <strong>${message.sender.name}:</strong> ${message.message || ''}`;

                // Check for attachments
                if (message.attachment) {
                    const attachmentUrl = `${baseUrl}${message.attachment}`;
                    const fileExtension = message.attachment.split('.').pop().toLowerCase();

                    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
                        // Image attachment
                        messageContent += `<div class="mt-2"><img src="${attachmentUrl}" alt="Attachment" style="max-width: 100%; border-radius: 5px;"></div>`;
                    } else if (['mp4', 'webm', 'ogg'].includes(fileExtension)) {
                        // Video attachment
                        messageContent += `<div class="mt-2"><video controls style="max-width: 100%; border-radius: 5px;">
                            <source src="${attachmentUrl}" type="video/${fileExtension}">
                            Your browser does not support the video tag.
                        </video></div>`;
                    } else if (['mp3', 'wav', 'ogg'].includes(fileExtension)) {
                        // Audio attachment
                        messageContent += `<div class="mt-2"><audio controls style="width: 100%;">
                            <source src="${attachmentUrl}" type="audio/${fileExtension}">
                            Your browser does not support the audio tag.
                        </audio></div>`;
                    } else {
                        // Generic file attachment (e.g., PDFs, docs, etc.)
                        messageContent += `<div class="mt-2"><a href="${attachmentUrl}" target="_blank" class="btn btn-primary btn-sm">Download Attachment</a></div>`;
                    }
                }

                messageContent += `</div>`;
                messageDiv.innerHTML = iconHTML + messageContent;
                messagesList.appendChild(messageDiv);
            });

            // Scroll to the bottom of the chat
            messagesList.scrollTop = messagesList.scrollHeight;

            // Highlight the active conversation
            const allLinks = document.querySelectorAll('#conversationList a');
            allLinks.forEach(link => link.classList.remove('active'));
            document.getElementById('chat-' + conversationId).classList.add('active');

            // Display the messages container and set the conversation ID
            document.getElementById('messagesContainer').style.display = 'block';
            document.getElementById('messagesContainer').setAttribute('data-conversation-id', conversationId);

            // Subscribe to Pusher for real-time updates
            pusher.subscribe(`conversation.${conversationId}`).bind('MessageSent', function(data) {
                const newMessage = data.message;
                const newMessageDiv = document.createElement('div');
                const isNewCurrentUser = newMessage.sender.id === {{ Auth::id() }};

                newMessageDiv.className = `d-flex align-items-start mb-2 ${isNewCurrentUser ? 'left-align' : ''}`;
                newMessageDiv.innerHTML = `<i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
                    <div class="p-2 rounded" style="background-color: ${isNewCurrentUser ? '#d1ecf1' : '#f8d7da'};">
                        <strong>${newMessage.sender.name}:</strong> ${newMessage.message || ''}`;
                
                // Check and render attachment for the new message
                if (newMessage.attachment) {
                    const newAttachmentUrl = `${baseUrl}${newMessage.attachment}`;
                    const newFileExtension = newMessage.attachment.split('.').pop().toLowerCase();

                    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(newFileExtension)) {
                        newMessageDiv.innerHTML += `<div class="mt-2"><img src="${newAttachmentUrl}" alt="Attachment" style="max-width: 100%; border-radius: 5px;"></div>`;
                    } else if (['mp4', 'webm', 'ogg'].includes(newFileExtension)) {
                        newMessageDiv.innerHTML += `<div class="mt-2"><video controls style="max-width: 100%; border-radius: 5px;">
                            <source src="${newAttachmentUrl}" type="video/${newFileExtension}">
                        </video></div>`;
                    } else if (['mp3', 'wav', 'ogg'].includes(newFileExtension)) {
                        newMessageDiv.innerHTML += `<div class="mt-2"><audio controls style="width: 100%;">
                            <source src="${newAttachmentUrl}" type="audio/${newFileExtension}">
                        </audio></div>`;
                    } else {
                        newMessageDiv.innerHTML += `<div class="mt-2"><a href="${newAttachmentUrl}" target="_blank" class="btn btn-primary btn-sm">Download Attachment</a></div>`;
                    }
                }

                newMessageDiv.innerHTML += `</div>`;
                messagesList.appendChild(newMessageDiv);
                messagesList.scrollTop = messagesList.scrollHeight;
            });
        });
}

    // Load group members when clicking "View Members"
// Load group members when clicking "View Members"
document.getElementById('viewMembersBtn').onclick = function() {
    const conversationId = document.getElementById('messagesContainer').getAttribute('data-conversation-id');
       if (!conversationId) {
        toastr.error("Please select a conversation first.");
        return;
    }
    
    fetch(`/conversations/${conversationId}/members`)
        .then(response => response.json())
        .then(members => {
            const membersList = document.getElementById('membersList');
            membersList.innerHTML = members.map(member => {
               return `
    <li class="d-flex justify-content-between align-items-center">
        <span>${member.name}</span>
        <button class="btn btn-sm bg-primary" onclick="startOneToOneChat(${member.id})">
            <i class="bi bi-chat"></i> <!-- Chat Icon -->
        </button>
    </li>
`;

            }).join('');
            new bootstrap.Modal(document.getElementById('membersModal')).show();
        });
};

function startOneToOneChat(memberId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const userId = document.querySelector('meta[name="user-id"]').content; // Use meta tag for Auth ID

    fetch('/create-one-to-one-conversation', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            user_id_1: userId, 
            user_id_2: memberId
        })
    })
    .then(response => response.json())  // Ensure JSON parsing
    .then(data => {
        if (data.id) {
            $('#membersModal').modal('hide');
            loadConversations();
            loadMessages(data.id);
        } else {
            alert(data.error || 'Failed to start the chat.');
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
        alert('Error: ' + error.message);
    });
}



function togglePin(conversationId, shouldPin) {
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
    fetch(`/api/conversations/${conversationId}/pin`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ pinned: shouldPin })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to update pin status');
            }
            // Reload conversations after pin status update
            loadConversations();
        })
        .catch(error => {
            console.error('Error toggling pin status:', error);
        });
}


    document.getElementById('sendMessageBtn').onclick = function() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value;
    const conversationId = document.getElementById('messagesContainer').getAttribute('data-conversation-id');
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
    const attachmentInput = document.getElementById('mediaInput'); // File input element
    const formData = new FormData();

    // Append the message, conversation ID, and CSRF token
    formData.append('message', message);
    formData.append('user_id', '{{ Auth::id() }}');
    formData.append('_token', csrfToken);

    // Append the file if an attachment is selected
    if (attachmentInput.files.length > 0) {
        formData.append('attachment', attachmentInput.files[0]);
    }

    if (message || attachmentInput.files.length > 0) {
        fetch(`/conversations/${conversationId}/messages`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(() => {
            messageInput.value = '';
            attachmentInput.value = ''; // Clear the file input
            loadMessages(conversationId); // Reload messages
        });
    }
};


    document.addEventListener('DOMContentLoaded', loadConversations);
</script>
@endsection
