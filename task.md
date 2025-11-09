What's Missing for Medium-like UX:

Inline formatting toolbar (appears on text selection)
Plus button for adding content blocks
Focus mode writing experience
Medium's distinctive typography and spacing
Floating toolbar behavior
Block-level interactions





error: starkit tool have the tip tap 
donot export formation tool 



I've made these changes to fix the infinite refresh:

Disabled notification polling temporarily
Removed automatic redirect on auth failure from API interceptor
Added useCallback to prevent function recreation
Temporarily disabled NotificationCenter in Header


Let me temporarily modify the notifications endpoint to return immediately without processing:



Now I've created a minimal HomePage with no API calls, no complex components, and no useEffect hooks. Let's test this:

Simplified HomePage - Removed all API calls, useEffect hooks, and complex components
Disabled React StrictMode - Removed double rendering in development
Disabled notifications endpoint - Made it return immediately
Removed NotificationCenter import - Completely removed from Header


Disabled service worker registration
✅ Added service worker cleanup
✅ Simplified HomePage to minimal version
✅ Disabled React StrictMode
✅ Removed NotificationCenter


  
Re-enable NotificationCenter (with proper error handling) --- done
Add article search functionality
Implement article detail pages
Add user authentication UI.   -- done 
Add commenting system. -- done