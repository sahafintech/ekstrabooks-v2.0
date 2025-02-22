import PropTypes from "prop-types";
import { cn } from "@/lib/utils";

const TableWrapper = ({ children, className }) => {
    return (
        <div className={cn("rounded-md border", className)}>
            {children}
        </div>
    );
};

TableWrapper.propTypes = {
    children: PropTypes.node.isRequired,
    className: PropTypes.string,
};

export default TableWrapper;
