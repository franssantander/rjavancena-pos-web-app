import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import ecommerceData from "@/data/ecommerce.json";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

interface ShopCategory {
  id: number;
  img: string;
  productName: string;
  brand: string;
  category: string;
  reviews: string;
  price: number;
}

const ShopCategory: React.FC = () => {
  const { category } = useParams();
  const [data, setData] = useState<ShopCategory[]>([]);

  useEffect(() => {
    const dataJSON = ecommerceData.data.filter(
      (item) => item.category === String(category)
    );
    setData(dataJSON);
  }, [category]);

  return (
    <>
      <div className="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 max-w-[1200px] m-auto">
        {data.map((item, index) => (
          <Card key={index}>
            <CardHeader>
              {item.img ? (
                <img src={item.img} alt={item.productName} />
              ) : (
                <Skeleton className="h-40 max-w-auto rounded-sm bg-neutral-400" />
              )}
              <CardTitle className="text-xl">{item.productName}</CardTitle>
              <CardDescription>{item.category}</CardDescription>
            </CardHeader>
            <CardContent>
              <p>Price: {item.price}</p>
            </CardContent>
            <CardFooter>
              <Button className="w-full font-bold">Add to Cart</Button>
            </CardFooter>
          </Card>
        ))}
      </div>
    </>
  );
};

export default ShopCategory;
