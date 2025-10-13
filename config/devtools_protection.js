/**
 * DevTools Protection
 * Discourages users from inspecting code via F12/DevTools
 * Note: This is not 100% foolproof but adds a layer of protection
 */

(function() {
    'use strict';
    
    // Disable right-click
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        showWarning('Right-click is disabled for security reasons.');
        return false;
    });
    
    // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
    document.addEventListener('keydown', function(e) {
        // F12
        if (e.keyCode === 123) {
            e.preventDefault();
            showWarning('Developer tools are disabled.');
            return false;
        }
        
        // Ctrl+Shift+I (Inspect)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
            e.preventDefault();
            showWarning('Developer tools are disabled.');
            return false;
        }
        
        // Ctrl+Shift+J (Console)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
            e.preventDefault();
            showWarning('Developer tools are disabled.');
            return false;
        }
        
        // Ctrl+Shift+C (Inspect Element)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
            e.preventDefault();
            showWarning('Developer tools are disabled.');
            return false;
        }
        
        // Ctrl+U (View Source)
        if (e.ctrlKey && e.keyCode === 85) {
            e.preventDefault();
            showWarning('Viewing source code is disabled.');
            return false;
        }
        
        // Ctrl+S (Save Page)
        if (e.ctrlKey && e.keyCode === 83) {
            e.preventDefault();
            showWarning('Saving page is disabled.');
            return false;
        }
    });
    
    // Detect if DevTools is open
    let devtoolsOpen = false;
    const threshold = 160;
    
    const detectDevTools = () => {
        const widthThreshold = window.outerWidth - window.innerWidth > threshold;
        const heightThreshold = window.outerHeight - window.innerHeight > threshold;
        
        if (widthThreshold || heightThreshold) {
            if (!devtoolsOpen) {
                devtoolsOpen = true;
                handleDevToolsOpen();
            }
        } else {
            devtoolsOpen = false;
        }
    };
    
    // Check for DevTools every 500ms
    setInterval(detectDevTools, 500);
    
    // Alternative DevTools detection using console
    const element = new Image();
    Object.defineProperty(element, 'id', {
        get: function() {
            devtoolsOpen = true;
            handleDevToolsOpen();
        }
    });
    
    setInterval(() => {
        console.clear();
        console.log(element);
    }, 1000);
    
    // Handle when DevTools is detected
    function handleDevToolsOpen() {
        // Clear console
        console.clear();
        
        // Show warning
        console.log('%c⚠️ WARNING', 'color: red; font-size: 40px; font-weight: bold;');
        console.log('%cDeveloper Tools Detected!', 'color: red; font-size: 20px;');
        console.log('%cUnauthorized access to system code is prohibited.', 'color: orange; font-size: 16px;');
        console.log('%cYour activity is being logged.', 'color: orange; font-size: 16px;');
        
        // Optional: Redirect or logout
        // window.location.href = 'logout.php';
        
        // Optional: Blur the page
        document.body.style.filter = 'blur(5px)';
        document.body.style.pointerEvents = 'none';
        
        // Show overlay warning
        showFullScreenWarning();
    }
    
    // Show warning message
    function showWarning(message) {
        // Remove existing warnings
        const existing = document.querySelector('.security-warning');
        if (existing) existing.remove();
        
        const warning = document.createElement('div');
        warning.className = 'security-warning';
        warning.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc2626;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            z-index: 999999;
            font-family: Arial, sans-serif;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
        `;
        warning.textContent = message;
        document.body.appendChild(warning);
        
        setTimeout(() => {
            warning.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => warning.remove(), 300);
        }, 3000);
    }
    
    // Show full screen warning
    function showFullScreenWarning() {
        const existing = document.querySelector('.devtools-warning-overlay');
        if (existing) return;
        
        const overlay = document.createElement('div');
        overlay.className = 'devtools-warning-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
            font-family: Arial, sans-serif;
        `;
        
        overlay.innerHTML = `
            <div style="text-align: center; max-width: 600px; padding: 40px;">
                <div style="font-size: 80px; margin-bottom: 20px;">⚠️</div>
                <h1 style="font-size: 36px; margin-bottom: 20px; color: #ef4444;">Security Alert</h1>
                <p style="font-size: 18px; margin-bottom: 20px; line-height: 1.6;">
                    Developer tools have been detected. Unauthorized access to system code is prohibited and may violate security policies.
                </p>
                <p style="font-size: 16px; color: #fbbf24; margin-bottom: 30px;">
                    Your activity is being logged for security purposes.
                </p>
                <button onclick="location.reload()" style="
                    background: #3b82f6;
                    color: white;
                    border: none;
                    padding: 15px 40px;
                    font-size: 16px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: bold;
                ">Close DevTools & Reload</button>
            </div>
        `;
        
        document.body.appendChild(overlay);
    }
    
    // Disable text selection (optional)
    document.addEventListener('selectstart', function(e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });
    
    // Disable drag and drop
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Clear console periodically
    setInterval(() => {
        console.clear();
    }, 2000);
    
    // Obfuscate console messages
    const originalLog = console.log;
    console.log = function() {
        originalLog.apply(console, ['🔒 Console access restricted']);
    };
    
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Prevent iframe embedding (clickjacking protection)
    if (window.top !== window.self) {
        window.top.location = window.self.location;
    }
    
    // Log security event
    console.warn('%c⚠️ Security Notice', 'color: orange; font-size: 16px; font-weight: bold;');
    console.warn('This system is protected. Unauthorized access attempts are logged.');
    
})();
