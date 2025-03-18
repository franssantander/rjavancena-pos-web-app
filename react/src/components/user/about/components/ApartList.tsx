interface ApartProps {
  title: string;
  desc: string;
}

const ApartList: React.FC<ApartProps> = ({ title, desc }) => {
  return (
    <>
      <div>
        <h1 className="font-bold text-lg text-textblack sm:text-lg">{title}</h1>
        <p className="text-xs pt-3 text-textblack sm:text-md sm:w-2/3">
          {desc}
        </p>
      </div>
    </>
  );
};

export default ApartList;
