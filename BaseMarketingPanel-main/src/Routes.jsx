import React from "react";
import { BrowserRouter, Routes as RouterRoutes, Route } from "react-router-dom";
import ScrollToTop from "components/ScrollToTop";
import ErrorBoundary from "components/ErrorBoundary";
import NotFound from "pages/NotFound";
import Dashboard from './pages/dashboard';
import ContentCalendar from './pages/content-calendar';
import PlatformConnections from './pages/platform-connections';
import ContentCreatorPageWithToast from './pages/content-creator';
import UserProfile from './pages/user-profile';
import MediaLibrary from './pages/media-library';

const Routes = () => {
  return (
    <BrowserRouter>
      <ErrorBoundary>
      <ScrollToTop />
      <RouterRoutes>
        {/* Define your route here */}
        <Route path="/" element={<Dashboard />} />
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/content-calendar" element={<ContentCalendar />} />
        <Route path="/platform-connections" element={<PlatformConnections />} />
        <Route path="/content-creator" element={<ContentCreatorPageWithToast />} />
        <Route path="/user-profile" element={<UserProfile />} />
        <Route path="/media-library" element={<MediaLibrary />} />
        <Route path="*" element={<NotFound />} />
      </RouterRoutes>
      </ErrorBoundary>
    </BrowserRouter>
  );
};

export default Routes;