<?php
namespace Atlas\Orm\DataSource;

use Atlas\Orm\AbstractAtlasContainer;

class SchoolAtlasContainer extends AbstractAtlasContainer
{
    protected function init()
    {
        $this->setMappers(
            Course\CourseMapper::CLASS,
            Degree\DegreeMapper::CLASS,
            Enrollment\EnrollmentMapper::CLASS,
            Gpa\GpaMapper::CLASS,
            Student\StudentMapper::CLASS
        );
    }
}
