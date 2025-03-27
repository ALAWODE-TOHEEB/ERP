// components/Topbar.jsx
export default function Topbar() {
    return (
      <header className="fixed top-0 left-0 right-0 h-16 bg-white shadow-md z-10">
        {/* Your topbar content (logo, navigation, user menu, etc.) */}
        <div className="container mx-auto h-full flex items-center px-4">
          <h1>Your App Name</h1>
        </div>
      </header>
    );
  }