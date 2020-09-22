import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

@Injectable()
export class SharedDashboardHeaderServiceService {

  private task = new Subject<any>();
  task$ = this.task.asObservable();

  updateTasks(tasks: any) {
    this.task.next(tasks);
  }
}