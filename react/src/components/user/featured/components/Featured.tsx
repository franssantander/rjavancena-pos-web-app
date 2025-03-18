import React from "react";
import { Skeleton } from "@/components/ui/skeleton";

import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
} from "@/components/ui/carousel";
import { Card, CardContent } from "@/components/ui/card";
import ecommerceData from "@/data/ecommerce.json";

const Featured: React.FC = () => {
  return (
    <>
      <div className="w-full h-full p-4 max-w-[1200px] m-auto my-20">
        <div>
          <div className="text-2xl font-bold underline underline-offset-8">
            Featured products
          </div>
          <p className="xs text-neutral-500 pt-3">
            Discover excellence in every detail with our Featured Product.
            Handpicked for quality and performance, it's the perfect choice for
            your next project.
          </p>
        </div>
        <div className="py-10 sm:mx-10">
          <Carousel className="w-full">
            <CarouselContent>
              {ecommerceData.data.map((data, index) => (
                <CarouselItem key={index} className="sm:basis-1/2 md:basis-1/3">
                  <div className="p-1">
                    <Card>
                      <CardContent className="flex aspect-square items-center justify-center p-6">
                        <div>
                          {data.img ? (
                            <img src={data.img} alt={data.productName} />
                          ) : (
                            <Skeleton className="h-40 w-72 rounded-sm bg-neutral-400" />
                          )}
                          <div className="flex flex-col gap-2">
                            <h1 className="text-xl font-semibold pt-4">
                              {data.productName}
                            </h1>
                            <p className="uppercase text-neutral-500 text-lg">
                              {data.brand}
                            </p>
                            <h1 className="font-bold text-lg flex gap-3 ">
                              Price:
                              <span className="font-thin">
                                {new Intl.NumberFormat("en-PH", {
                                  style: "currency",
                                  currency: "PHP",
                                }).format(data.price)}
                              </span>
                            </h1>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  </div>
                </CarouselItem>
              ))}
            </CarouselContent>
            <CarouselPrevious className="hidden sm:block" />
            <CarouselNext className="hidden sm:block" />
          </Carousel>
        </div>
      </div>
    </>
  );
};

export default Featured;
