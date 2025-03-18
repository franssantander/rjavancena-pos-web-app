import ApartList from "./ApartList.tsx";

const AboutContent: React.FC = () => {
  const apart = [
    {
      title: "Uncompromising Quality:",
      desc: "We stand by the principle of delivering products that exceed expectations. Our commitment to quality ensures that every item you purchase from us is built to last.",
    },
    {
      title: "Expert Curation:",
      desc: "Our team of experts curates a selection of products that align with industry standards, ensuring you have access to the latest and most reliable solutions.",
    },
    {
      title: "Customer-Centric Approach:",
      desc: " Your satisfaction is our priority. Our customer support team is dedicated to assisting you at every step, providing expert guidance and solutions tailored to your needs.",
    },
  ];

  return (
    <>
      <div className="w-full max-w-[1200px] m-auto">
        <div className="py-32">
          <div className="flex flex-col gap-6 p-4 md:text-center md:max-w-[490px] md:m-auto md:gap-10 lg:max-w-[660px] lg:gap-20">
            <div>
              <h1 className="font-bold text-3xl lg:text-4xl">About us</h1>
              <p className="text-xs pt-3 sm:text-base">
                Welcome to RJ Avancena Enterprises – Your Trusted Partner in
                Quality Hardware Solutions!
              </p>
            </div>
            <div>
              <h1 className="font-bold text-3xl lg:text-4xl">Our Story</h1>
              <p className="text-xs pt-3 sm:text-base">
                At RJ Avancena Enterprises, we embarked on a journey with a
                singular vision: to redefine the hardware retail experience.
                Founded with a passion for delivering excellence, we have grown
                into a reliable destination for all your hardware needs.
              </p>
            </div>
            <div>
              <h1 className="font-bold text-3xl lg:text-4xl">Our Mission</h1>
              <p className="text-xs pt-3 sm:text-base">
                Empowering Your Projects: Our mission is to empower your
                projects by providing a diverse range of high-quality tools,
                building materials, plumbing essentials, and electrical
                components. We believe that every project deserves the best, and
                we're here to deliver just that.
              </p>
            </div>
          </div>
          <div className="py-14">
            <h1 className="font-bold text-3xl p-4 lg:p-0">
              What Sets Us Apart?
            </h1>
            <div className="relative h-32 sm:h-44 my-9">
              <img
                className="w-full h-full object-cover"
                src="https://images.unsplash.com/photo-1589939705384-5185137a7f0f?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                alt=""
              />
              <div className="absolute inset-0 bg-black opacity-40"></div>
            </div>
            <div className="p-4 lg:p-0 mt-10 flex flex-col gap-6 sm:grid sm:grid-cols-2">
              {apart.map((item, index) => (
                <ApartList key={index} title={item.title} desc={item.desc} />
              ))}
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default AboutContent;
