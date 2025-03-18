import _ from "lodash";
import {
  DropdownMenuItem,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import { Label } from "@/components/ui/label";
import { Card } from "@/components/ui/card";
import { Link } from "react-router-dom";
import { IconBuildingWarehouse, IconCircleFilled } from "@tabler/icons-react";

const NotificationCard = ({ item, innerRef, handleClickNotif }) => {
  return (
    <>
      <>
        <DropdownMenuItem ref={innerRef} className="grid" key={item}>
          <Link
            onClick={() => handleClickNotif(item)}
            to={`/app/inventory/inventory-child/${item.url_view}`}
            className="flex gap-3 items-center"
          >
            <Card className="w-9 flex items-center justify-center">
              <IconBuildingWarehouse size={20} />
            </Card>
            <div>
              <Label className="text-xs capitalize font-semibold">
                {item.name}
              </Label>
              <div className="flex items-center gap-2">
                <span className="text-neutral-500 text-xs">{item.time}</span>
                <IconCircleFilled className="text-neutral-400" size={5} />
                <span className="text-neutral-500 text-xs">
                  {_.startCase(item.page)}
                </span>
              </div>
            </div>
            {item.is_read === 0 && (
              <IconCircleFilled
                className="right-4 flex absolute text-red-500"
                size={12}
              />
            )}
          </Link>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
      </>
    </>
  );
};

export default NotificationCard;
