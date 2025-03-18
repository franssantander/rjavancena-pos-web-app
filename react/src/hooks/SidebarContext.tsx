import React, { createContext, useContext, useState } from "react";

interface SidebarContextType {
  open: boolean;
  setOpen: React.Dispatch<React.SetStateAction<boolean>>;
}

const SidebarContext = createContext<SidebarContextType | undefined>(undefined);

export const SidebarProvider: React.FC = ({ children }) => {
  const [open, setOpen] = useState<boolean>(true);

  const handleExpandSidebar = (open: boolean, detailsOpen: any) => {
    if (open && detailsOpen) {
      setOpen(true);
    }
  };

  return (
    <SidebarContext.Provider
      value={{
        open,
        setOpen,
        handleExpandSidebar,
      }}
    >
      {children}
    </SidebarContext.Provider>
  );
};

export const useSidebar = (): SidebarContextType => {
  return useContext(SidebarContext);
};
