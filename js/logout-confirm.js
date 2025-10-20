// Custom logout confirmation modal
function showLogoutConfirmation(logoutUrl) {
    // Create modal HTML
    const modalHTML = `
        <div id="logoutConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]" style="display: flex;">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6">
                <div class="text-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Confirm Logout</h3>
                    <p class="text-sm text-gray-600 mb-6">Are you sure you want to logout?</p>
                    <div class="flex gap-3">
                        <button onclick="closeLogoutModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2.5 rounded-lg font-medium transition">
                            Cancel
                        </button>
                        <button onclick="confirmLogout('${logoutUrl}')" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutConfirmModal');
    if (modal) {
        modal.remove();
    }
}

function confirmLogout(url) {
    window.location.href = url;
}
