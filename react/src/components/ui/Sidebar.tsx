import React from "react";
import logo from "../assets/svg/rjlogo-text.svg";
import { Icon } from "@iconify/react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { buttonVariants } from "@/components/ui/button";
import _ from "lodash";
import {
  Drawer,
  DrawerContent,
  DrawerFooter,
  DrawerHeader,
  DrawerTitle,
  DrawerTrigger,
} from "@/components/ui/drawer";

import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { Link, useLocation } from "react-router-dom";
import { useSidebar } from "@/hooks/SidebarContext";
import { useAppSelector } from "@/app/hooks";

const Sidebar: React.FC = () => {
  const { open, setOpen, handleExpandSidebar } = useSidebar();

  const location = useLocation();
  const sideNavRoutes = useAppSelector((state) => state?.user?.sidebar?.nav_links);

  return (
    <>
      <Drawer direction="left">
        <DrawerTrigger className="lg:hidden rounded-none shadow-none" asChild>
          <Button className="mx-4 mt-3 rounded-sm absolute z-50">
            <Icon fontSize={24} icon="heroicons:bars-3-16-solid" />
          </Button>
        </DrawerTrigger>
        <DrawerContent>
          <div className="w-64 h-full">
            <DrawerHeader>
              <DrawerTitle className="font-semibold">RJ AVANCENA</DrawerTitle>
            </DrawerHeader>
            <div className="p-4 pb-0 px-3">
              <div className="flex flex-col gap-2">
                {sideNavRoutes &&
                  sideNavRoutes.map((menu: any, index: number) => (
                    <React.Fragment key={index}>
                      {menu.submenus ? (
                        <Accordion type="single" collapsible>
                          <AccordionItem
                            className="border-b-0"
                            value={`${index}`}
                          >
                            <AccordionTrigger
                              onClick={() => handleExpandSidebar(true, true)}
                              className={cn(
                                buttonVariants({
                                  size: "sm",
                                  variant: "ghost",
                                }),
                                "justify-between w-0 hover:no-underline font-medium text-neutral-600"
                              )}
                            >
                              <div className="flex items-center gap-3 text-neutral-600">
                                <Icon fontSize={22} icon={menu.icon} />
                                <span
                                  className={`${
                                    !open
                                      ? "scale-0 ease-in-out transition-all"
                                      : "ease-in-out scale-100 transition-all"
                                  }`}
                                >
                                  {menu.title}
                                </span>
                              </div>
                            </AccordionTrigger>

                            {menu.submenus.map(
                              (submenu: any, index: number) => (
                                <AccordionContent
                                  className="pb-2 pt-2"
                                  key={index}
                                >
                                  {open ? (
                                    <div className="w-full flex flex-col">
                                      <Link
                                        className={cn(
                                          buttonVariants({
                                            size: "sm",
                                            variant: "ghost",
                                          }),
                                          "justify-between ml-7 text-neutral-600 flex gap-10"
                                        )}
                                        to={`app${submenu.path}`}
                                      >
                                        {submenu.title}
                                      </Link>
                                    </div>
                                  ) : null}
                                </AccordionContent>
                              )
                            )}
                          </AccordionItem>
                        </Accordion>
                      ) : (
                        <Link
                          className={cn(
                            buttonVariants({
                              size: "sm",
                              variant: "ghost",
                            }),
                            "justify-between font-medium"
                          )}
                          to={`/app${menu.path}`}
                        >
                          <div
                            className={`flex items-center gap-3 text-start text-neutral-600`}
                          >
                            <Icon fontSize={22} icon={menu.icon} />
                            <span
                              className={`${
                                !open
                                  ? "scale-0 ease-in-out transition-all"
                                  : "ease-in-out scale-100 transition-all"
                              }`}
                            >
                              {menu.title}
                            </span>
                          </div>
                        </Link>
                      )}
                    </React.Fragment>
                  ))}
              </div>
            </div>
            <DrawerFooter></DrawerFooter>
          </div>
        </DrawerContent>
      </Drawer>
      <div
        className={`min-h-screen hidden border-r p-2 lg:flex flex-col bg-white transition-all relative ${
          open ? "w-48" : "w-14"
        }`}
      >
        <div className="flex items-center justify-between">
          <img
            className={`transition-all ease-in-out ${
              open ? "w-28" : "w-0 ease-in-out"
            }`}
            src={logo}
            alt="RJ Avancena Logo"
          />

          <Button size="sm" variant="ghost" onClick={() => setOpen(!open)}>
            <Icon icon="heroicons-outline:chevron-double-right" />
          </Button>
        </div>
        <nav className="gap-2 flex flex-col pt-10 ease-in-out transition-all">
          {sideNavRoutes &&
            sideNavRoutes.map((menu: any, index: number) => (
              <React.Fragment key={index}>
                {menu.submenus ? (
                  <Accordion type="single" collapsible>
                    <AccordionItem className="border-b-0" value={`${index}`}>
                      <AccordionTrigger
                        open={open}
                        onClick={() => handleExpandSidebar(true, true)}
                        className={cn(
                          buttonVariants({
                            size: "sm",
                            variant: "ghost",
                          }),
                          "justify-between w-0 hover:no-underline font-medium text-neutral-600"
                        )}
                      >
                        <div
                          className={`flex items-center gap-3 text-neutral-600`}
                        >
                          <Icon fontSize={17} icon={menu.icon} />
                          <span
                            className={`${
                              !open
                                ? "scale-0 hidden ease-in-out transition-all"
                                : "ease-in-out scale-100 transition-all"
                            }`}
                          >
                            {menu.title}
                          </span>
                        </div>
                      </AccordionTrigger>

                      {menu.submenus.map((submenu: any, index: number) => (
                        <AccordionContent className="pb-1" key={index}>
                          {open ? (
                            <div className="w-full flex flex-col">
                              <Link
                                className={cn(
                                  buttonVariants({
                                    size: "sm",
                                    variant: "ghost",
                                  }),
                                  `justify-between ml-7 text-neutral-600 flex gap-10  ${
                                    location.pathname === submenu.path &&
                                    "bg-primary text-white hover:bg-primary/90 hover:text-white"
                                  }`
                                )}
                                to={`/app${submenu.path}`}
                              >
                                {submenu.title}
                              </Link>
                            </div>
                          ) : null}
                        </AccordionContent>
                      ))}
                    </AccordionItem>
                  </Accordion>
                ) : (
                  <Link
                    className={cn(
                      buttonVariants({
                        size: "sm",
                        variant: "ghost",
                      }),
                      `justify-between font-medium text-neutral-600  ${
                        location.pathname === `/app${menu.path}` &&
                        "bg-[#f66359] text-white hover:bg-bgrjavancena/90 hover:text-white"
                      } ${
                        _.includes(
                          location.pathname,
                          "/app/inventory/inventory-child"
                        ) &&
                        menu.path === "/inventory" &&
                        "bg-[#f66359] text-white hover:bg-bgrjavancena/90 hover:text-white"
                      }`
                    )}
                    to={`/app${menu.path}`}
                  >
                    <div className={`flex items-center gap-3 text-start`}>
                      <Icon fontSize={17} icon={menu.icon} />
                      <span
                        className={`${
                          !open
                            ? "scale-0 hidden ease-in-out transition-all"
                            : "ease-in-out scale-100 transition-all"
                        }`}
                      >
                        {menu.title}
                      </span>
                    </div>
                  </Link>
                )}
              </React.Fragment>
            ))}
        </nav>
      </div>
    </>
  );
};

export default Sidebar;
