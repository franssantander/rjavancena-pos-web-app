import React from "react";
import Hero from "../../../components/user/hero/components/Hero";
import Featured from "../../../components/user/featured/components/Featured";
import ShopCategory from "../../../components/user/category/components/ShopCategory";
import ShopProduct from "../../../components/user/shop/components/ShopProduct";
import Section from "../../../components/user/section/components/Section";

const ExternalPage: React.FC = () => {
  return (
    <>
      <Hero />
      <Featured />
      <ShopCategory />
      <ShopProduct />
      <Section />
    </>
  );
};

export default ExternalPage;
