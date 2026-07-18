@extends('layouts.app')
@section('title', brand_title('Chat'))
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
        max-height: 200px;
        object-fit: contain;
    }
    .chat-item audio {
        width: 100%;
    }
    #messagesList::-webkit-scrollbar, #conversationList::-webkit-scrollbar {
        width: 6px;
    }
    #messagesList::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    #messagesList::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    #messagesList::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    #messagesList {
        scrollbar-width: thin;
        scrollbar-color: #888 #f1f1f1;
        padding-right: 5px;
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
    /* New styles for additional features */
    .search-container {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    .message-time {
        font-size: 0.75rem;
        color: #6c757d;
        margin-left: 5px;
    }
    .typing-indicator {
        font-style: italic;
        color: #6c757d;
        padding: 5px 10px;
    }
    .message-actions {
        display: none;
        position: absolute;
        right: 10px;
        top: 5px;
    }
    .message-wrapper:hover .message-actions {
        display: block;
    }
    .message-actions button {
        background: none;
        border: none;
        padding: 2px 5px;
    }
    .media-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 10px;
    }
    .media-thumbnail {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 5px;
        cursor: pointer;
    }
    .media-modal-img {
        max-width: 100%;
        max-height: 80vh;
    }
    .shortcut-hint {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 10px;
        border-radius: 5px;
        z-index: 1000;
    }
    .unread-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background-color: red;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div id="scheduling" class="page-wrapper security_board">
    <div class="content">
        <div class="row mt-4">
            <div class="d-md-flex d-block align-items-center justify-content-between mb-1">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Chats</h2>
                </div>
            </div>

            <!-- Sidebar for Conversations List -->
            <div class="col-md-4 d-flex flex-column" id="conversationSidebar">
                <div class="card flex-grow-1">
                    <div class="card-header text-white d-flex justify-content-between align-items-center">
                        <h5>{{ __('messages.all_conversation') }}</h5>
                        <button class="btn btn-sm btn-light" id="searchConversationsBtn">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div class="search-container" style="display: none;">
                        <input type="text" class="form-control" id="conversationSearch" placeholder="Search conversations...">
                    </div>
                    <div class="list-group list-group-flush" id="conversationList" style="max-height:70vh; overflow-y:scroll">
                        <!-- Conversations will be dynamically added here -->
                    </div>
                </div>
            </div>

            <!-- Messages Section -->
            <div class="col-md-8 d-flex flex-column" id="messagesSection" style="height: auto; display: none;">
                <div id="messagesContainer" class="flex-grow-1">
                    <div class="card h-100" style="min-height: 70vh;">
                        <div class="card-header text-white d-flex justify-content-between align-items-center">
                            <button class="btn btn-sm btn-light" id="backButton" style="display: none;">Back to Conversations</button>
                            <span id="currentConversationTitle">{{ __('messages.message') }}</span>
                            <div>
                                <button class="btn btn-sm btn-light" id="viewMembersBtn">{{ __('messages.members_list') }}</button>
                                <button class="btn btn-primary btn-sm text-white" data-bs-toggle="modal" data-bs-target="#oneToOneModal">
                                    {{ __('messages.start_chat') }}
                                </button>
                                <button class="btn btn-primary btn-sm text-white" data-bs-toggle="modal" data-bs-target="#groupChatModal">
                                    {{ __('New Group') }}
                                </button>
                                <button class="btn btn-info btn-sm text-white" id="viewMediaBtn" title="View Media">
                                    <i class="bi bi-images"></i>
                                </button>
                            </div>
                        </div>
                        <div id="typingIndicator" class="typing-indicator" style="display: none;"></div>
                        <div id="messagesList" style="max-height:70vh; overflow-y:scroll" class="card-body">
                            <div style="max-height:75vh; overflow-y: auto; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                <div id="defaultMessage" class="text-center" style="display: flex; flex-direction: column; align-items: center;">
                                    <i class="bi bi-chat-dots" style="font-size: 50px;"></i>
                                    <p>{{ __('messages.select_chat_prompt') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="input-group">
                                <input type="text" class="form-control" id="messageInput" placeholder="{{ __('messages.write_message_here') }}">
                                <button class="btn btn-light" id="emojiBtn">😊</button>
                                <div id="emojiPickerContainer" style="position: absolute; display: block; z-index: 1000;"></div>

                                <input type="file" id="mediaInput" class="d-none" accept="image/*,video/*,audio/*">
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

                            <!-- Delete Conversation Confirmation Modal -->
                            <div class="modal fade" id="deleteConversationModal" tabindex="-1" aria-labelledby="deleteConversationModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteConversationModalLabel">Delete Conversation</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p id="deleteConversationText">Are you sure you want to delete this conversation? This action cannot be undone.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="button" id="confirmDeleteConversationBtn" class="btn btn-danger">Delete</button>
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

<!-- Media Gallery Modal -->
<div class="modal fade" id="mediaGalleryModal" tabindex="-1" aria-labelledby="mediaGalleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaGalleryModalLabel">Media Gallery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="media-gallery" id="mediaGallery">
                    <!-- Media thumbnails will be dynamically added here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Media Preview Modal -->
<div class="modal fade" id="mediaPreviewModal" tabindex="-1" aria-labelledby="mediaPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaPreviewModalLabel">Media Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" class="media-modal-img" style="display: none;">
                <video id="previewVideo" controls class="media-modal-img" style="display: none;"></video>
                <audio id="previewAudio" controls style="display: none;"></audio>
            </div>
        </div>
    </div>
</div>

<!-- One-to-One Chat Modal -->
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
                                <option value="{{ $user->id }}"data-first="{{ strtolower($user->first_name) }}"
                                    data-last="{{ strtolower($user->last_name) }}">{{ $user->first_name }} {{ $user->last_name }}</option>
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

<!-- Group Chat Modal -->
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

<!-- Keyboard Shortcuts Hint -->
<div class="shortcut-hint" id="shortcutHint" style="display: none;">
    <h6>Keyboard Shortcuts</h6>
    <ul class="list-unstyled">
        <li><kbd>Ctrl</kbd> + <kbd>Enter</kbd> - Send message</li>
        <li><kbd>Esc</kbd> - Close current modal</li>
        <li><kbd>Ctrl</kbd> + <kbd>K</kbd> - Search conversations</li>
        <li><kbd>Ctrl</kbd> + <kbd>M</kbd> - Toggle media gallery</li>
    </ul>
    <button class="btn btn-sm btn-secondary mt-2" onclick="toggleShortcutHint()">Close</button>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/pusher-js@7.0.3/dist/web/pusher.min.js"></script>
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

    // Global variables
    let currentConversationId = null;
    let typingTimeout = null;
    let pusher = null;
    let typingChannels = {};

    // Initialize Pusher
    function initializePusher() {
        pusher = new Pusher('f45c24b39e2a5f3b2239', { 
            cluster: 'mt1',
            encrypted: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }
        });
    }

    // Emoji Picker
    const pickerContainer = document.getElementById('emojiPickerContainer');
    const emojiButton = document.getElementById('emojiBtn');
    const messageInput = document.getElementById('messageInput');
    const picker = new EmojiMart.Picker({
        onEmojiSelect: emoji => {
            messageInput.value += emoji.native;
            messageInput.focus();
        },
        theme: 'light',
    });
    pickerContainer.appendChild(picker);
    pickerContainer.style.display = 'none';

    // Toggle emoji picker
    emojiButton.addEventListener('click', (e) => {
        e.stopPropagation();
        pickerContainer.style.display = pickerContainer.style.display === 'none' ? 'block' : 'none';
        const rect = emojiButton.getBoundingClientRect();
        pickerContainer.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
        pickerContainer.style.right = (window.innerWidth - rect.right) + 'px';
    });

    // Hide picker on outside click
    document.addEventListener('click', (e) => {
        if (!pickerContainer.contains(e.target) && e.target !== emojiButton) {
            pickerContainer.style.display = 'none';
        }
    });

    // Load conversations with search functionality
    function loadConversations(searchQuery = '') {
        fetch(`/load/conversations?search=${encodeURIComponent(searchQuery)}`)
            .then(response => response.json())
            .then(conversations => {
                const conversationList = document.getElementById('conversationList');
                conversationList.innerHTML = '';

                // Separate and sort conversations by pinned status and unread messages
                const sortedConversations = [
                    ...conversations.filter(c => c.pinned && c.unread_count > 0),
                    ...conversations.filter(c => c.pinned && c.unread_count === 0),
                    ...conversations.filter(c => !c.pinned && c.unread_count > 0),
                    ...conversations.filter(c => !c.pinned && c.unread_count === 0)
                ];
                console.log(sortedConversations)

                sortedConversations.forEach(conversation => {
                    const conversationItem = document.createElement('a');
                    conversationItem.href = '#';
                    conversationItem.className = 'list-group-item list-group-item-action d-flex align-items-center position-relative';
                    conversationItem.id = `chat-${conversation.id}`;
                    conversationItem.onclick = () => loadMessages(conversation.id);

                    // Content wrapper
                    const contentWrapper = document.createElement('div');
                    contentWrapper.className = 'd-flex align-items-center flex-grow-1';

                    // Generate content based on conversation type
                    if (conversation.type === 'group') {
                        const logoUrl = conversation.icon_path 
                            ? `${baseUrl}/${conversation.icon_path}` 
                            : 'https://banffventureforum.com/wp-content/uploads/2019/08/no-photo-icon-22.png';
                        const groupName = conversation.name || 'Group';

                        contentWrapper.innerHTML = `
                            <img class="chat-logo" src="${logoUrl}" alt="${groupName} Logo" onerror="this.src='https://banffventureforum.com/wp-content/uploads/2019/08/no-photo-icon-22.png'">
                            <div class="d-flex flex-column">
                                <span>${groupName}</span>
                                <small class="text-muted last-message-preview">${conversation.last_message || ''}</small>
                            </div>
                        `;
                    } else {
                        conversation.participants.forEach(user => {
                            const loggedInUserId = {{ Auth::id() }};
                            if (user.id !== loggedInUserId) {
                                const profilePictureUrl = user.profile_pic 
                                    ? `${baseUrl}${user.profile_pic}` 
                                    : "https://banffventureforum.com/wp-content/uploads/2019/08/no-photo-icon-22.png";
                                const firstName = user.first_name || '';
                                const lastName = user.last_name || '';
                                const userName = firstName+' '+lastName || 'Unknown User';

                                contentWrapper.innerHTML = `
                                    <img class="chat-logo" src="${profilePictureUrl}" alt="${userName} Picture" onerror="this.src='https://cdn-icons-png.flaticon.com/512/1053/1053244.png'">
                                    <div class="d-flex flex-column">
                                        <span>${userName}</span>
                                        <small class="text-muted last-message-preview">${conversation.last_message || ''}</small>
                                    </div>
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
                    };

                    // Add delete button next to pin
                    const deleteButton = document.createElement('label');
                    deleteButton.className = 'pin-icon ms-2 delete-icon';
                    const delIcon = document.createElement('i');
                    delIcon.className = 'bi bi-trash';
                    deleteButton.appendChild(delIcon);

                    deleteButton.onclick = event => {
                        event.stopPropagation();
                        openDeleteConversationModal(conversation.id, conversation.name || 'this conversation');
                    };

                    // Add unread badge if there are unread messages
                    if (conversation.unread_count > 0) {
                        const unreadBadge = document.createElement('span');
                        unreadBadge.className = 'unread-badge';
                        unreadBadge.textContent = conversation.unread_count;
                        contentWrapper.appendChild(unreadBadge);
                    }

                    // Append content, pin and delete buttons
                    conversationItem.appendChild(contentWrapper);
                    conversationItem.appendChild(pinButton);
                    conversationItem.appendChild(deleteButton);
                    conversationList.appendChild(conversationItem);
                });

                // Highlight active conversation if any
                if (currentConversationId) {
                    const activeChat = document.getElementById(`chat-${currentConversationId}`);
                    if (activeChat) {
                        activeChat.classList.add('active');
                    }
                }
            })
            .catch(error => console.error('Error loading conversations:', error));
    }

    // Load messages with enhanced features
    function loadMessages(conversationId) {
        currentConversationId = conversationId;
        fetch(`/conversations/${conversationId}/messages`)
            .then(response => response.json())
            .then(data => {
                const { messages, conversation } = data;
                const messagesList = document.getElementById('messagesList');
                messagesList.innerHTML = '';

                // Set conversation title
                document.getElementById('currentConversationTitle').textContent = conversation?.name;
console.log(messages)
                if (messages.length === 0) {
                    messagesList.innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-chat-square-text" style="font-size: 3rem;"></i>
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    `;
                } else {
                    messages.forEach((message, index) => {
                        const isCurrentUser = message.sender.id === {{ Auth::id() }};
                        const profilePictureUrl = message.sender.profile_pic 
                            ? `${baseUrl}${message.sender.profile_pic}` 
                            : "https://banffventureforum.com/wp-content/uploads/2019/08/no-photo-icon-22.png";
                        
                        // Group messages by the same sender
                        const prevMessage = index > 0 ? messages[index - 1] : null;
                        const showSenderInfo = !prevMessage || prevMessage.sender.id !== message.sender.id;
                        
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message-wrapper mb-2 ${isCurrentUser ? 'd-flex justify-content-end' : 'd-flex justify-content-start'}`;
                        
                        // User icon and name (only shown for first message in a sequence)
                        const senderInfo = showSenderInfo ? `
                            <div class="d-flex align-items-center mb-1">
                                <img src="${profilePictureUrl}" class="user-icon me-2 rounded-circle" onerror="this.src='https://cdn-icons-png.flaticon.com/512/1053/1053244.png'">
                                <small class="text-muted">${message.sender.first_name}</small>
                            </div>
                        ` : '';
                        
                        // Format message time
                        const messageTime = new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        
                        // Message content with attachment handling
                        let messageContent = `
                            <div class="p-2 chat-item rounded position-relative" style="background-color: ${isCurrentUser ? '#d1ecf1' : '#f8d7da'};">
                                <div class="message-actions">
                                    <button class="text-muted" onclick="copyMessage('${message.id}')" title="Copy">
                                        <i class="bi bi-files"></i>
                                    </button>
                                    ${isCurrentUser ? `
                                    <button class="text-muted" onclick="deleteMessage('${message.id}')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    ` : ''}
                                </div>
                                ${message.message || ''}
                                <small class="message-time">${messageTime}</small>
                        `;

                        // Check for attachments
                        if (message.attachment) {
                            const attachmentUrl = `${baseUrl}${message.attachment}`;
                            const fileExtension = message.attachment.split('.').pop().toLowerCase();

                            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
                                messageContent += `
                                    <div class="mt-2">
                                        <img src="${attachmentUrl}" alt="Attachment" class="img-thumbnail" style="max-width: 100%; cursor: pointer;" 
                                             onclick="previewMedia('${attachmentUrl}', 'image')">
                                    </div>
                                `;
                            } else if (['mp4', 'webm', 'ogg'].includes(fileExtension)) {
                                messageContent += `
                                    <div class="mt-2">
                                        <video controls style="max-width: 100%; border-radius: 5px;">
                                            <source src="${attachmentUrl}" type="video/${fileExtension}">
                                        </video>
                                    </div>
                                `;
                            } else if (['mp3', 'wav', 'ogg'].includes(fileExtension)) {
                                messageContent += `
                                    <div class="mt-2">
                                        <audio controls style="width: 100%;">
                                            <source src="${attachmentUrl}" type="audio/${fileExtension}">
                                        </audio>
                                    </div>
                                `;
                            } else {
                                messageContent += `
                                    <div class="mt-2">
                                        <a href="${attachmentUrl}" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="bi bi-download"></i> Download Attachment
                                        </a>
                                    </div>
                                `;
                            }
                        }

                        messageContent += `</div>`;
                        messageDiv.innerHTML = senderInfo + messageContent;
                        messagesList.appendChild(messageDiv);
                    });
                }

                // Scroll to the bottom of the chat
                messagesList.scrollTop = messagesList.scrollHeight;

                // Highlight the active conversation
                const allLinks = document.querySelectorAll('#conversationList a');
                allLinks.forEach(link => link.classList.remove('active'));
                const activeChat = document.getElementById(`chat-${conversationId}`);
                if (activeChat) {
                    activeChat.classList.add('active');
                    // Clear unread badge
                    const unreadBadge = activeChat.querySelector('.unread-badge');
                    if (unreadBadge) {
                        unreadBadge.remove();
                    }
                }

                // Show messages container

                // Mark messages as read
                markMessagesAsRead(conversationId);

                // Subscribe to Pusher for real-time updates
                subscribeToConversation(conversationId);
                
                // Load media gallery for this conversation
                loadMediaGallery(conversationId);
            });
    }

    // Subscribe to Pusher channel for a conversation
    function subscribeToConversation(conversationId) {
        if (!pusher) {
            initializePusher();
        }

        // Unsubscribe from previous typing channel if exists
        if (typingChannels[conversationId]) {
            typingChannels[conversationId].unbind('client-typing');
            delete typingChannels[conversationId];
        }

        // Subscribe to messages channel
        const channel = pusher.subscribe(`private-conversation.${conversationId}`);
        
        // Listen for new messages
        channel.bind('MessageSent', function(data) {
            if (currentConversationId === conversationId) {
                // If we're currently viewing this conversation, add the message
                appendNewMessage(data.message);
                markMessagesAsRead(conversationId);
            } else {
                // Otherwise, update the conversation list and show unread badge
                loadConversations();
            }
        });

        // Listen for typing events
        const typingChannel = pusher.subscribe(`presence-conversation.${conversationId}`);
        typingChannels[conversationId] = typingChannel;
        
        typingChannel.bind('client-typing', function(data) {
            if (data.userId !== {{ Auth::id() }}) {
                showTypingIndicator(data.userName);
            }
        });
    }

    // Append a new message to the chat
    function appendNewMessage(message) {
        const messagesList = document.getElementById('messagesList');
        const isCurrentUser = message.sender.id === {{ Auth::id() }};
        const profilePictureUrl = message.sender.profile_pic 
            ? `${baseUrl}${message.sender.profile_pic}` 
            : "https://banffventureforum.com/wp-content/uploads/2019/08/no-photo-icon-22.png";
        
        // Check if we should show sender info (compare with last message)
        const lastMessageElement = messagesList.lastElementChild;
        let showSenderInfo = true;
        if (lastMessageElement) {
            const lastMessageSender = lastMessageElement.querySelector('.user-icon');
            if (lastMessageSender && lastMessageSender.src.includes(profilePictureUrl)) {
                showSenderInfo = false;
            }
        }
        
        const messageTime = new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message-wrapper mb-2 ${isCurrentUser ? 'd-flex justify-content-end' : 'd-flex justify-content-start'}`;
        
        // User icon and name (only shown for first message in a sequence)
        const senderInfo = showSenderInfo ? `
            <div class="d-flex align-items-center mb-1">
                <img src="${profilePictureUrl}" class="user-icon me-2 rounded-circle" onerror="this.src='https://cdn-icons-png.flaticon.com/512/1053/1053244.png'">
                <small class="text-muted">${message.sender.first_name}</small>
            </div>
        ` : '';
        
        // Message content
        let messageContent = `
            <div class="p-2 chat-item rounded position-relative" style="background-color: ${isCurrentUser ? '#d1ecf1' : '#f8d7da'};">
                <div class="message-actions">
                    <button class="text-muted" onclick="copyMessage('${message.id}')" title="Copy">
                        <i class="bi bi-files"></i>
                    </button>
                    ${isCurrentUser ? `
                    <button class="text-muted" onclick="deleteMessage('${message.id}')" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                    ` : ''}
                </div>
                ${message.message || ''}
                <small class="message-time">${messageTime}</small>
        `;

        // Check for attachments
        if (message.attachment) {
            const attachmentUrl = `${baseUrl}${message.attachment}`;
            const fileExtension = message.attachment.split('.').pop().toLowerCase();

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
                messageContent += `
                    <div class="mt-2">
                        <img src="${attachmentUrl}" alt="Attachment" class="img-thumbnail" style="max-width: 100%; cursor: pointer;" 
                             onclick="previewMedia('${attachmentUrl}', 'image')">
                    </div>
                `;
            } else if (['mp4', 'webm', 'ogg'].includes(fileExtension)) {
                messageContent += `
                    <div class="mt-2">
                        <video controls style="max-width: 100%; border-radius: 5px;">
                            <source src="${attachmentUrl}" type="video/${fileExtension}">
                        </video>
                    </div>
                `;
            } else if (['mp3', 'wav', 'ogg'].includes(fileExtension)) {
                messageContent += `
                    <div class="mt-2">
                        <audio controls style="width: 100%;">
                            <source src="${attachmentUrl}" type="audio/${fileExtension}">
                        </audio>
                    </div>
                `;
            } else {
                messageContent += `
                    <div class="mt-2">
                        <a href="${attachmentUrl}" target="_blank" class="btn btn-primary btn-sm">
                            <i class="bi bi-download"></i> Download Attachment
                        </a>
                    </div>
                `;
            }
        }

        messageContent += `</div>`;
        messageDiv.innerHTML = senderInfo + messageContent;
        messagesList.appendChild(messageDiv);
        
        // Scroll to the bottom
        messagesList.scrollTop = messagesList.scrollHeight;
        
        // Add to media gallery if it's a media message
        if (message.attachment && ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg'].includes(fileExtension)) {
            addToMediaGallery(message.attachment, fileExtension);
        }
    }

    // Show typing indicator
    function showTypingIndicator(userName) {
        const typingIndicator = document.getElementById('typingIndicator');
        typingIndicator.textContent = `${userName} is typing...`;
        typingIndicator.style.display = 'block';
        
        // Hide after 3 seconds
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            typingIndicator.style.display = 'none';
        }, 3000);
    }

    // Mark messages as read
    function markMessagesAsRead(conversationId) {
        fetch(`/conversations/${conversationId}/mark-as-read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
    }

    // Toggle pin status of a conversation
    function togglePin(conversationId, shouldPin) {
        fetch(`/api/conversations/${conversationId}/pin`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ pinned: shouldPin })
        })
        .then(() => loadConversations())
        .catch(error => console.error('Error toggling pin status:', error));
    }

    // Send message with typing indicator
    document.getElementById('messageInput').addEventListener('input', function() {
        if (currentConversationId && this.value.trim() !== '') {
            const typingChannel = typingChannels[currentConversationId];
            if (typingChannel) {
                typingChannel.trigger('client-typing', {
                    userId: {{ Auth::id() }},
                    userName: '{{ Auth::user()->name }}'
                });
            }
        }
    });

    // Send message with Ctrl+Enter shortcut
    document.getElementById('messageInput').addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('sendMessageBtn').click();
        }
    });

    // Send message function
    document.getElementById('sendMessageBtn').onclick = function() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value;
        const conversationId = currentConversationId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const attachmentInput = document.getElementById('mediaInput');
        const formData = new FormData();

        if (!conversationId) {
            toastr.error("Please select a conversation first.");
            return;
        }

        if (!message && attachmentInput.files.length === 0) {
            toastr.error("Message cannot be empty.");
            return;
        }

        formData.append('message', message);
         formData.append('user_id', {{ Auth::id() }});
        formData.append('_token', csrfToken);
        if (attachmentInput.files.length > 0) {
            formData.append('attachment', attachmentInput.files[0]);
        }

        fetch(`/conversations/${conversationId}/send-messages`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(() => {
            messageInput.value = '';
            attachmentInput.value = '';
            messageInput.focus();
            loadMessages(conversationId);
        })
        .catch(error => {
            console.error('Error sending message:', error);
            toastr.error("Failed to send message.");
        });
    };

    // Handle media input change
    document.getElementById('mediaInput').addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            const fileSize = file.size / 1024 / 1024; // in MB
            const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const validVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
            const validAudioTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg'];
            
            if (fileSize > 10) {
                toastr.error("File size should be less than 10MB");
                this.value = '';
                return;
            }
            
            if (!validImageTypes.includes(file.type) && !validVideoTypes.includes(file.type) && !validAudioTypes.includes(file.type)) {
                toastr.error("Invalid file type. Only images, videos and audio are allowed.");
                this.value = '';
                return;
            }
            
            // Auto-send if it's an image/video/audio and message is empty
            if (document.getElementById('messageInput').value.trim() === '' && 
                (validImageTypes.includes(file.type) || validVideoTypes.includes(file.type) || validAudioTypes.includes(file.type))) {
              //  document.getElementById('sendMessageBtn').click();
            }
        }
    });

    // Load group members
    document.getElementById('viewMembersBtn').onclick = function() {
        if (!currentConversationId) {
            toastr.error("Please select a conversation first.");
            return;
        }
        
        fetch(`/conversations/${currentConversationId}/members`)
            .then(response => response.json())
            .then(members => {
                const membersList = document.getElementById('membersList');
                membersList.innerHTML = members.map(member => `
                    <li class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="${member.profile_pic ? baseUrl + member.profile_pic : 'https://banffventureforum.com/wp-content/uploads/2019/08/no-photo-icon-22.png'}" 
                                 class="chat-logo me-2" onerror="this.src='https://banffventureforum.com/wp-content/uploads/2019/08/no-photo-icon-22.png'">
                            <span>${member.first_name}</span>
                        </div>
                        <button class="btn btn-sm bg-primary" onclick="startOneToOneChat(${member.id})">
                            <i class="bi bi-chat"></i>
                        </button>
                    </li>
                `).join('');
                new bootstrap.Modal(document.getElementById('membersModal')).show();
            });
    };

    // Start one-to-one chat with a member
    function startOneToOneChat(memberId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const userId = {{ Auth::id() }};

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
        .then(response => response.json())
        .then(data => {
            if (data.id) {
                $('#membersModal').modal('hide');
                loadConversations();
                loadMessages(data.id);
            } else {
                toastr.error(data.error || 'Failed to start the chat.');
            }
        })
        .catch(error => {
            console.error("Error:", error);
            toastr.error('Error: ' + error.message);
        });
    }

    // Load media gallery for a conversation
    function loadMediaGallery(conversationId) {
        fetch(`/conversations/${conversationId}/media`)
            .then(response => response.json())
            .then(mediaItems => {
                const mediaGallery = document.getElementById('mediaGallery');
                mediaGallery.innerHTML = '';
                
                mediaItems.forEach(media => {
                    console.log(media)
                    const fileExtension = media.attachment.split('.').pop().toLowerCase();
                    const mediaUrl = `${baseUrl}${media.attachment}`;
                    
                    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
                        mediaGallery.innerHTML += `
                            <img src="${mediaUrl}" class="media-thumbnail" 
                                 onclick="previewMedia('${mediaUrl}', 'image')">
                        `;
                    } else if (['mp4', 'webm', 'ogg'].includes(fileExtension)) {
                        mediaGallery.innerHTML += `
                            <video class="media-thumbnail" onclick="previewMedia('${mediaUrl}', 'video')">
                                <source src="${mediaUrl}" type="video/${fileExtension}">
                            </video>
                        `;
                    }
                });
            });
    }

    // Add to media gallery
    function addToMediaGallery(mediaPath, fileExtension) {
        const mediaGallery = document.getElementById('mediaGallery');
        const mediaUrl = `${baseUrl}${mediaPath}`;
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
            mediaGallery.innerHTML += `
                <img src="${mediaUrl}" class="media-thumbnail" 
                     onclick="previewMedia('${mediaUrl}', 'image')">
            `;
        } else if (['mp4', 'webm', 'ogg'].includes(fileExtension)) {
            mediaGallery.innerHTML += `
                <video class="media-thumbnail" onclick="previewMedia('${mediaUrl}', 'video')">
                    <source src="${mediaUrl}" type="video/${fileExtension}">
                </video>
            `;
        }
    }

    // Preview media in modal
    function previewMedia(mediaUrl, type) {
        const previewImage = document.getElementById('previewImage');
        const previewVideo = document.getElementById('previewVideo');
        const previewAudio = document.getElementById('previewAudio');
        
        previewImage.style.display = 'none';
        previewVideo.style.display = 'none';
        previewAudio.style.display = 'none';
        
        if (type === 'image') {
            previewImage.src = mediaUrl;
            previewImage.style.display = 'block';
        } else if (type === 'video') {
            previewVideo.innerHTML = `<source src="${mediaUrl}" type="video/${mediaUrl.split('.').pop()}">`;
            previewVideo.style.display = 'block';
        } else if (type === 'audio') {
            previewAudio.innerHTML = `<source src="${mediaUrl}" type="audio/${mediaUrl.split('.').pop()}">`;
            previewAudio.style.display = 'block';
        }
        
        new bootstrap.Modal(document.getElementById('mediaPreviewModal')).show();
    }

    // Copy message to clipboard
    function copyMessage(messageId) {
        const messageElement = document.querySelector(`[onclick="copyMessage('${messageId}')"]`).closest('.chat-item');
        const messageText = messageElement.innerText.replace(/CopyDelete/g, '').trim();
        
        navigator.clipboard.writeText(messageText)
            .then(() => toastr.success("Message copied to clipboard"))
            .catch(() => toastr.error("Failed to copy message"));
    }

    // Delete message
    function deleteMessage(messageId) {
        if (!confirm("Are you sure you want to delete this message?")) return;
        
        fetch(`/messages/${messageId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => {
            if (response.ok) {
                toastr.success("Message deleted");
                loadMessages(currentConversationId);
            } else {
                toastr.error("Failed to delete message");
            }
        });
    }

    // Toggle conversation search
    document.getElementById('searchConversationsBtn').addEventListener('click', function() {
        const searchContainer = document.querySelector('.search-container');
        searchContainer.style.display = searchContainer.style.display === 'none' ? 'block' : 'none';
        if (searchContainer.style.display === 'block') {
            document.getElementById('conversationSearch').focus();
        }
    });

    // Delete conversation flow
    let conversationToDeleteId = null;
    function openDeleteConversationModal(id, name) {
        conversationToDeleteId = id;
        const textEl = document.getElementById('deleteConversationText');
        textEl.textContent = `Are you sure you want to delete "${name}"? This action cannot be undone.`;
        const modalEl = new bootstrap.Modal(document.getElementById('deleteConversationModal'));
        modalEl.show();
    }

    document.getElementById('confirmDeleteConversationBtn').addEventListener('click', function() {
        if (!conversationToDeleteId) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        fetch(`/conversations/${conversationToDeleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(resp => {
            if (!resp.ok) throw new Error('Delete failed');
            return resp.json().catch(() => ({}));
        })
        .then(() => {
            toastr.success('Conversation deleted');
            // close modal
            bootstrap.Modal.getInstance(document.getElementById('deleteConversationModal')).hide();
            // refresh conversations
            loadConversations();
            // clear current view if deleted was active
            if (currentConversationId == conversationToDeleteId) {
                currentConversationId = null;
                document.getElementById('messagesSection').style.display = 'none';
            }
        })
        .catch(err => {
            console.error(err);
            toastr.error('Failed to delete conversation');
        });
    });

    // Search conversations
    document.getElementById('conversationSearch').addEventListener('input', function() {
        loadConversations(this.value);
    });

    // View media gallery
    document.getElementById('viewMediaBtn').addEventListener('click', function() {
        if (!currentConversationId) {
            toastr.error("Please select a conversation first.");
            return;
        }
        new bootstrap.Modal(document.getElementById('mediaGalleryModal')).show();
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+K to search conversations
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            document.querySelector('.search-container').style.display = 'block';
            document.getElementById('conversationSearch').focus();
        }
        
        // Ctrl+M to toggle media gallery
        if (e.ctrlKey && e.key === 'm') {
            e.preventDefault();
            document.getElementById('viewMediaBtn').click();
        }
        
        // Esc to close modals
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            if (openModals.length > 0) {
                bootstrap.Modal.getInstance(openModals[0]).hide();
            }
        }
        
        // Show shortcut hint on Ctrl+/
        if (e.ctrlKey && e.key === '/') {
            e.preventDefault();
            toggleShortcutHint();
        }
    });

    // Toggle shortcut hint
    function toggleShortcutHint() {
        const hint = document.getElementById('shortcutHint');
        hint.style.display = hint.style.display === 'none' ? 'block' : 'none';
    }

    // Initialize the app
    document.addEventListener('DOMContentLoaded', function() {
        initializePusher();
        loadConversations();
        
        // Show welcome message with shortcuts
        setTimeout(() => {
            toastr.info("Press Ctrl+/ to see keyboard shortcuts");
        }, 2000);
    });
</script>
@endsection