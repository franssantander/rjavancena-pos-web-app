import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent } from "@/components/ui/card";
import { ScrollArea } from "@/components/ui/scroll-area";

const ItemsList: React.FC = (props) => {
  const formatted = new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  });
  const { items } = props.customerData;

  return (
    <>
      <h1 className="font-medium text-sm">
        Total items ({items.reduce((acc, item) => acc + item.count, 0)})
      </h1>
      <ScrollArea className="h-[400px] w-full p-4">
        {items &&
          items?.map((item, index) => (
            <Card key={index} className="my-3">
              <CardContent className="flex justify-between items-center p-3">
                <div className="flex items-center gap-3">
                  <Skeleton className="w-14 h-14" />
                  <div>
                    <h1 className="text-sm font-semibold">{item.name}</h1>
                    <p className="text-xs text-neutral-400">{item.category}</p>
                  </div>
                </div>
                <div>
                  <h1 className="text-xs text-neutral-500">
                    ({formatted.format(item.total_price)}) x {item.count}
                  </h1>
                </div>
              </CardContent>
            </Card>
          ))}
      </ScrollArea>
    </>
  );
};

export default ItemsList;
