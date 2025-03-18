import React, { useState } from "react";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuList,
  NavigationMenuTrigger,
} from "@/components/ui/navigation-menu";
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";

import { ScrollArea, ScrollBar } from "@/components/ui/scroll-area";
import { List } from "@radix-ui/react-navigation-menu";
import { Link } from "react-router-dom";
import ecommerceData from "@/data/ecommerce.json";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

const ShopProduct: React.FC = () => {
  const categoryList = [
    {
      category: "Hardware",
      subCategory: [
        {
          category: "Power Tools",
          path: "/power-tools",
        },
        {
          category: "Hand Tools",
          path: "/hand-tools",
        },
        {
          category: "Lawn and Garden Tools",
          path: "/lawnandgarden-tools",
        },
        {
          category: "Paint and sundries",
          path: "/paintandsundries-tools",
        },
        {
          category: "Lightning",
          path: "/lightning-tools",
        },
        {
          category: "Electrical",
          path: "/electrical-tools",
        },
        {
          category: "Plumbing",
          path: "/plumbing-tools",
        },
      ],
    },
    {
      category: "Home Improvement",
      subCategory: [
        {
          category: "Decor",
          path: "/decor",
        },
        {
          category: "Essential",
          path: "/essential",
        },
        {
          category: "Office Supplies",
          path: "/office-supplies",
        },
        {
          category: "Furniture",
          path: "/furniture",
        },
        {
          category: "Chemicals",
          path: "/chemicals",
        },
        {
          category: "Cleaning",
          path: "/cleaning",
        },
      ],
    },
    {
      category: "Appliance",
      subCategory: [
        {
          category: "Home Appliance",
          path: "/home-appliance",
        },
        {
          category: "Big Appliance",
          path: "/big-appliance",
        },
        {
          category: "Kitchen Appliance",
          path: "/kitchen-appliance",
        },
        {
          category: "Cooling",
          path: "/cooling",
        },
      ],
    },
  ];

  const pageSize = 16;
  const [currentPage, setCurrentPage] = useState(1);

  // Calculate start and end index based on current page
  const startIndex = (currentPage - 1) * pageSize;
  const endIndex = Math.min(startIndex + pageSize, ecommerceData.data.length);

  // Slice the data array to display only items for the current page
  const currentData = ecommerceData.data.slice(startIndex, endIndex);

  return (
    <>
      <div className="lg:max-w-[1200px] m-auto">
        <div className="w-full h-full p-4 m-auto my-1">
          <div>
            <div className="text-2xl font-bold underline underline-offset-8">
              Shop by Category
            </div>
            <p className="xs text-neutral-500 pt-3">
              Browse our extensive range of products conveniently organized into
              categories, making it effortless to find exactly what you need.
            </p>
          </div>
          <div className="py-5">
            <NavigationMenu className="flex">
              {categoryList.map((categ, index) => (
                <NavigationMenuList key={index}>
                  <NavigationMenuItem>
                    <NavigationMenuTrigger className="font-medium text-xl">
                      {categ.category}
                    </NavigationMenuTrigger>

                    <NavigationMenuContent>
                      <ul className="w-96">
                        {categ.subCategory.map((subCateg) => (
                          <List
                            className="p-2"
                            key={subCateg.category}
                            title={subCateg.category}
                          >
                            <Link to={`/shop/${subCateg.category}`}>
                              {subCateg.category}
                            </Link>
                          </List>
                        ))}
                      </ul>
                    </NavigationMenuContent>
                  </NavigationMenuItem>
                </NavigationMenuList>
              ))}
            </NavigationMenu>
          </div>
        </div>
        <div className="w-full h-full p-4 m-auto my-2">
          <div className="text-md font-medium text-neutral-500">
            Shop all Products
          </div>
          <div className="my-4 flex flex-col gap-10 w-auto sm:grid sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
            {currentData.map((prod, index) => (
              <Card className="w-full" key={index}>
                <CardHeader>
                  {prod.img ? (
                    <img
                      className="w-auto"
                      src={prod.img}
                      alt={prod.productName}
                    />
                  ) : (
                    <Skeleton className="h-40 w-full rounded-sm bg-neutral-400" />
                  )}
                  <CardTitle className="text-xl">{prod.productName}</CardTitle>
                  <CardDescription>{prod.category}</CardDescription>
                </CardHeader>
                <CardContent>
                  <p className="font-bold text-md">Price: <span className="font-thin">{prod.price}</span></p>
                </CardContent>
                <CardFooter>
                  <Button className="w-full font-bold">Add to Cart</Button>
                </CardFooter>
              </Card>
            ))}
          </div>
          <Pagination className="flex justify-end float-right">
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious
                  className={
                    currentPage === 1
                      ? "pointer-events-none opacity-50"
                      : "cursor-pointer"
                  }
                  onClick={() => setCurrentPage(currentPage - 1)}
                />
              </PaginationItem>
              <PaginationItem>
                <PaginationNext
                  className={
                    endIndex === ecommerceData.data.length
                      ? "pointer-events-none opacity-50"
                      : "cursor-pointer"
                  }
                  onClick={() => setCurrentPage(currentPage + 1)}
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </div>
      </div>
    </>
  );
};

export default ShopProduct;
