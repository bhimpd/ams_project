<?php


class PaginationHelper
{
    public static function paginate($currentPage, $perPage, $totalData)
    {
        $offSet = ($currentPage - 1) * $perPage;
        $totalPage = ceil($totalData / $perPage);

        return
            [
                "offSet" => $offSet,
                "perPage" => $perPage,
                "totalPage" => $totalPage
            ];
    }
}
