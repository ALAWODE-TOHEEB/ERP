import Sidebar from "./sidebar/index";
import Topbar from "./TopBar/Topbar";

// export default RootLayout;
function RootLayout({ children }) {
  return (
    <div className="flex flex-col min-h-screen">
      {/* Fixed Topbar */}
      <Topbar />
      
      <div className="flex flex-1">
        {/* Fixed Sidebar */}
        <Sidebar />
        
        {/* Scrollable Main Content */}
        <main className="max-w-5xl flex-1 mx-auto py-4 mt-16">
          {children}
        </main>
      </div>
    </div>
  );
}

export default RootLayout;
